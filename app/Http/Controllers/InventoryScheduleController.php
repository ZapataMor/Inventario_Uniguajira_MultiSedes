<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\Inventory;
use App\Models\InventorySchedule;
use App\Models\InventoryScheduleEntryImage;
use App\Services\Schedules\ScheduleEvidenceService;
use App\Services\Schedules\ScheduleReceiptService;
use App\Services\Schedules\ScheduleSignatureService;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Modulo "Programacion de mantenimientos".
 *
 * Al crear un mantenimiento se diligencia la solicitud de servicio del
 * formato RA-F-33 en tres etapas: informacion del solicitante, tipos de
 * servicio solicitados y descripcion de la actividad (con el bloque y los
 * salones donde se hara). A partir de ahi se genera el codigo publico que
 * alimenta el QR y el enlace del formulario externo.
 * Las labores documentadas por personas externas entran por
 * PublicScheduleController.
 *
 * El QR es de un solo uso: cuando alguien diligencia el formulario, la
 * programacion queda cerrada y la tarjeta muestra lo documentado en lugar
 * del QR. Para una nueva labor se crea otra programacion.
 */
class InventoryScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleEvidenceService $evidence,
        private readonly ScheduleReceiptService $receipts,
        private readonly ScheduleSignatureService $signatures,
    ) {}

    /**
     * Crear, editar y eliminar programaciones queda restringido
     * a quien puede escribir en la sede: administradores y super administradores-administradores.
     */
    private function autorizarGestion(): void
    {
        $user = Auth::user();

        abort_if(! $user || ! $user->isAdministrator(), 403);
    }

    /**
     * Listado principal. Responde vista completa o solo la seccion
     * `content` cuando la peticion llega por AJAX (navegacion SPA).
     */
    public function index(Request $request)
    {
        // En el portal central solo se consultan los mantenimientos ya realizados
        // de cada sede; crear y gestionar programaciones sigue siendo operativo.
        if (! tenant()) {
            if (! $request->user()?->isGlobalAdmin()) {
                return redirect()->route('portal.index');
            }

            return $this->portalIndex($request);
        }

        $search = trim((string) $request->input('search', ''));

        $schedules = InventorySchedule::query()
            ->with(['inventories.group', 'entry.images'])
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('requester_name', 'like', "%{$search}%")
                    ->orWhere('requester_dependency', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->get();

        $inventories = Inventory::with('group:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_id']);

        $filters = ['search' => $search];
        $timezone = tenant()?->branding?->timezone_value ?? 'America/Bogota';

        // Firma guardada de quien consulta: permite descargar el formato
        // marcando "Firmar con firma guardada" en lugar de volver a firmar.
        $savedSignature = $this->signatures->savedDataUrl(Auth::user());

        $data = compact('schedules', 'inventories', 'filters', 'timezone', 'savedSignature');

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('schedules.index', $data);

            return $view->renderSections()['content'];
        }

        return view('schedules.index', $data);
    }

    /**
     * Catalogo del portal: mantenimientos realizados (programaciones ya
     * diligenciadas) agrupados por sede, en modo solo lectura.
     */
    private function portalIndex(Request $request)
    {
        $schedulesBySede = $this->getCompletedSchedulesBySedeForPortal();

        $data = compact('schedulesBySede');

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('schedules.portal', $data);

            return $view->renderSections()['content'];
        }

        return view('schedules.portal', $data);
    }

    /**
     * POST /api/schedules/create
     */
    public function store(Request $request): JsonResponse
    {
        $this->autorizarGestion();

        $data = $this->validateSchedule($request);

        $schedule = InventorySchedule::create($data['attributes'] + [
            'code' => InventorySchedule::generateCode(),
            'is_open' => true,
            'created_by' => Auth::id(),
        ]);

        $schedule->inventories()->sync($data['inventory_ids']);

        ActivityLogger::created(InventorySchedule::class, $schedule->id, $schedule->title);

        return response()->json([
            'success' => true,
            'message' => 'Programación creada correctamente.',
            'data' => $this->formatSchedule($schedule->fresh(['inventories.group', 'entry'])),
        ]);
    }

    /**
     * POST /api/schedules/update
     */
    public function update(Request $request): JsonResponse
    {
        $this->autorizarGestion();

        $request->validate(['id' => ['required', 'integer', 'exists:inventory_schedules,id']]);

        $schedule = InventorySchedule::with('inventories')->findOrFail($request->input('id'));

        // Una programacion ya diligenciada es historico: no se reabre ni se reedita.
        if ($schedule->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta programación ya fue diligenciada y no puede modificarse. Crea una nueva.',
            ], 422);
        }

        $data = $this->validateSchedule($request);

        $oldValues = $schedule->only(array_keys($data['attributes'])) + [
            'inventory_ids' => $schedule->inventories->pluck('id')->all(),
        ];
        $oldValues['requested_at'] = $schedule->requested_at?->format('Y-m-d');

        $schedule->update($data['attributes']);
        $schedule->inventories()->sync($data['inventory_ids']);

        ActivityLogger::updated(
            InventorySchedule::class,
            $schedule->id,
            $schedule->title,
            $oldValues,
            $data['attributes'] + ['inventory_ids' => $data['inventory_ids']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Programación actualizada correctamente.',
            'data' => $this->formatSchedule($schedule->fresh(['inventories.group', 'entry'])),
        ]);
    }

    /**
     * DELETE /api/schedules/delete/{id}
     *
     * Elimina la programacion y, en cascada, las labores documentadas.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->autorizarGestion();

        $schedule = InventorySchedule::findOrFail($id);
        $title = $schedule->title;

        // Las filas de evidencia caen en cascada, pero los archivos no:
        // hay que borrar la carpeta de la programacion a mano.
        $this->evidence->purge($schedule);

        $schedule->delete();

        ActivityLogger::deleted(InventorySchedule::class, $id, $title);

        return response()->json([
            'success' => true,
            'message' => 'Programación eliminada correctamente.',
        ]);
    }

    /**
     * GET /api/schedules/{id}/entries
     *
     * Labores documentadas desde el formulario publico, con sus evidencias.
     */
    public function entries(Request $request, int $id): JsonResponse
    {
        return $this->runForRequestedTenant(
            $request,
            fn (?Tenant $portalTenant) => $this->entriesResponse($id, $portalTenant)
        );
    }

    /**
     * Desde el portal llega `?tenant=slug`: la programacion se lee en la base
     * de esa sede y los enlaces de las evidencias conservan la sede.
     */
    private function entriesResponse(int $id, ?Tenant $portalTenant): JsonResponse
    {
        $schedule = InventorySchedule::with(['inventories.group', 'entries.images'])->findOrFail($id);
        $timezone = ($portalTenant ?? tenant())?->branding?->timezone_value ?? 'America/Bogota';
        $imageParams = $portalTenant ? ['tenant' => $portalTenant->slug, 'portal' => 1] : [];

        return response()->json([
            'success' => true,
            'schedule' => $this->formatSchedule($schedule, $portalTenant?->slug),
            // El formato se descarga firmado desde el modal de firma, solo dentro de la sede.
            'can_download' => $portalTenant === null && $schedule->isCompleted(),
            'entries' => $schedule->entries->map(function ($entry) use ($schedule, $timezone, $imageParams) {
                // El folio se deriva del codigo de la programacion: se
                // enlaza a mano para no consultarla una vez por labor.
                $entry->setRelation('schedule', $schedule);

                return [
                    'id' => $entry->id,
                    'receipt_code' => $entry->receipt_code,
                    'equipment' => $entry->equipment_label,
                    'action' => $entry->action,
                    'materials' => $entry->materials,
                    'performed_by' => $entry->performed_by,
                    'started_at' => $entry->started_at?->format('d/m/Y H:i'),
                    'finished_at' => $entry->finished_at?->format('d/m/Y H:i'),
                    'duration' => $entry->duration_label,
                    'registered_at' => $entry->registeredAtLabel($timezone),
                    'images' => $entry->images->map(fn ($image) => [
                        'id' => $image->id,
                        'url' => route('schedules.image', ['imageId' => $image->id] + $imageParams),
                        'description' => $image->description,
                        'size_label' => $image->size_label,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * GET /api/schedules/{id}/receipt/preview
     *
     * Vista previa del formato RA-F-33 diligenciado, sin la firma de quien
     * recibe. Es lo que se muestra antes de firmar para descargar.
     */
    public function receiptPreview(int $id): Response
    {
        $schedule = InventorySchedule::with(['inventories.group', 'entry'])->findOrFail($id);

        abort_if(! $schedule->isCompleted(), 404);

        return $this->receipts->preview($schedule);
    }

    /**
     * POST /api/schedules/{id}/receipt
     *
     * Descarga del formato firmado por quien lo recibe en la sede. Se firma
     * dibujando en el lienzo o con la firma guardada del usuario; al dibujar
     * se puede pedir que esa firma quede guardada para las proximas veces.
     */
    public function receipt(Request $request, int $id): Response
    {
        $schedule = InventorySchedule::with(['inventories.group', 'entry'])->findOrFail($id);

        abort_if(! $schedule->isCompleted(), 404);

        $user = Auth::user();

        $request->validate([
            'use_saved' => ['nullable', 'boolean'],
            'save_signature' => ['nullable', 'boolean'],
            'signature' => ['nullable', 'string'],
        ]);

        if ($request->boolean('use_saved')) {
            $file = $this->signatures->savedFile($user);

            if ($file === null) {
                throw ValidationException::withMessages([
                    'signature' => 'No tienes una firma guardada. Firma en el recuadro.',
                ]);
            }

            $signature = (string) file_get_contents($file);
        } else {
            $signature = $this->signatures->decode($request->input('signature'), 'signature');

            if ($request->boolean('save_signature')) {
                $this->signatures->save($user, $signature);
            }
        }

        // Firmar el formato si se audita: deja constancia de quien lo recibio.
        ActivityLogger::custom(
            'update',
            "Firmó y descargó el formato RA-F-33 de la programación: {$schedule->title}",
            [
                'model' => 'InventorySchedule',
                'model_id' => $schedule->id,
                'new_values' => ['recibida_por' => $user->name],
            ]
        );

        return $this->receipts->download($schedule, null, [
            'name' => (string) $user->name,
            'signature' => $signature,
        ]);
    }

    /**
     * DELETE /api/schedules/signature
     *
     * Olvida la firma guardada del usuario autenticado.
     */
    public function forgetSignature(): JsonResponse
    {
        $this->signatures->forget(Auth::user());

        return response()->json([
            'success' => true,
            'message' => 'Tu firma guardada se eliminó.',
        ]);
    }

    /**
     * GET /schedules/evidencias/{imageId}
     *
     * Sirve una evidencia fotografica al personal de la sede.
     */
    public function image(Request $request, int $imageId): BinaryFileResponse
    {
        return $this->runForRequestedTenant($request, function () use ($imageId) {
            $image = InventoryScheduleEntryImage::findOrFail($imageId);

            $path = $this->evidence->absolutePath($image);

            abort_if($path === null, 404);

            return response()->file($path, [
                'Cache-Control' => 'private, max-age=3600',
            ]);
        });
    }

    /**
     * POST /api/schedules/toggle-open
     *
     * Abre o cierra la recepcion de respuestas del formulario publico.
     */
    public function toggleOpen(Request $request): JsonResponse
    {
        $this->autorizarGestion();

        $request->validate(['id' => ['required', 'integer', 'exists:inventory_schedules,id']]);

        $schedule = InventorySchedule::findOrFail($request->input('id'));

        // El QR es de un solo uso: una vez diligenciado no se puede reabrir.
        if ($schedule->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta programación ya fue diligenciada. Crea una nueva para generar otro QR.',
            ], 422);
        }

        $schedule->is_open = ! $schedule->is_open;
        $schedule->save();

        ActivityLogger::updated(
            InventorySchedule::class,
            $schedule->id,
            $schedule->title,
            ['is_open' => ! $schedule->is_open],
            ['is_open' => $schedule->is_open]
        );

        return response()->json([
            'success' => true,
            'message' => $schedule->is_open
                ? 'El formulario público quedó habilitado.'
                : 'El formulario público quedó cerrado.',
            'is_open' => $schedule->is_open,
        ]);
    }

    /**
     * Reglas compartidas por store() y update().
     *
     * Devuelve los atributos listos para guardar (incluido el nombre, que
     * se arma a partir de la solicitud) y los salones seleccionados.
     *
     * @return array{attributes: array<string, mixed>, inventory_ids: array<int, int>}
     */
    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            // Etapa 1: informacion del solicitante
            'requester_name' => ['required', 'string', 'max:120'],
            'requester_position' => ['required', 'string', 'max:120'],
            'requester_dependency' => ['required', 'string', 'max:120'],
            'filing_number' => ['nullable', 'string', 'max:30'],
            'requested_at' => ['required', 'date'],

            // Etapa 2: tipo de servicio solicitado
            'service_types' => ['nullable', 'array'],
            'service_types.*' => ['string', Rule::in(array_keys(InventorySchedule::SERVICE_TYPES))],
            'service_other_enabled' => ['nullable', 'boolean'],
            'service_other' => ['nullable', 'string', 'max:150', 'required_if:service_other_enabled,1'],

            // Etapa 3: descripcion de la actividad
            'activity_type' => ['required', Rule::in(array_keys(InventorySchedule::ACTIVITY_TYPES))],
            'maintenance_type' => ['required', Rule::in(array_keys(InventorySchedule::MAINTENANCE_TYPES))],
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'inventory_ids' => ['required', 'array', 'min:1'],
            'inventory_ids.*' => ['integer', 'exists:inventories,id'],
        ], [
            'requester_name.required' => 'Escribe el nombre del solicitante.',
            'requester_position.required' => 'Escribe el cargo del solicitante.',
            'requester_dependency.required' => 'Escribe la dependencia del solicitante.',
            'requested_at.required' => 'Indica la fecha de la solicitud.',
            'service_other.required_if' => 'Describe el otro servicio solicitado.',
            'activity_type.required' => 'Indica el nombre de la actividad.',
            'maintenance_type.required' => 'Indica el tipo de mantenimiento.',
            'group_id.required' => 'Selecciona el bloque.',
            'inventory_ids.required' => 'Selecciona al menos un salón o sala del bloque.',
            'inventory_ids.min' => 'Selecciona al menos un salón o sala del bloque.',
            'inventory_ids.*.exists' => 'Alguna de las ubicaciones seleccionadas ya no existe.',
        ]);

        $serviceTypes = array_values(array_unique($data['service_types'] ?? []));
        $serviceOther = $request->boolean('service_other_enabled')
            ? trim((string) ($data['service_other'] ?? ''))
            : null;

        if ($serviceTypes === [] && blank($serviceOther)) {
            throw ValidationException::withMessages([
                'service_types' => 'Marca al menos un tipo de servicio o describe otro.',
            ]);
        }

        $inventoryIds = array_values(array_unique(array_map('intval', $data['inventory_ids'])));

        // Los salones deben pertenecer al bloque elegido: la seleccion es
        // "bloque y luego sus salones", no una mezcla de varios bloques.
        $outsideBlock = Inventory::whereIn('id', $inventoryIds)
            ->where('group_id', '!=', $data['group_id'])
            ->exists();

        if ($outsideBlock) {
            throw ValidationException::withMessages([
                'inventory_ids' => 'Los salones seleccionados deben pertenecer al bloque elegido.',
            ]);
        }

        return [
            'attributes' => [
                'title' => InventorySchedule::buildTitle(
                    $data['activity_type'],
                    $data['maintenance_type'],
                    InventorySchedule::serviceLabelsFor($serviceTypes, $serviceOther)
                ),
                'requester_name' => trim($data['requester_name']),
                'requester_position' => trim($data['requester_position']),
                'requester_dependency' => trim($data['requester_dependency']),
                'filing_number' => filled($data['filing_number'] ?? null) ? trim($data['filing_number']) : null,
                'requested_at' => $data['requested_at'],
                'service_types' => $serviceTypes,
                'service_other' => filled($serviceOther) ? $serviceOther : null,
                'activity_type' => $data['activity_type'],
                'maintenance_type' => $data['maintenance_type'],
            ],
            'inventory_ids' => $inventoryIds,
        ];
    }

    /**
     * Estructura JSON compartida por las respuestas del modulo.
     */
    private function formatSchedule(InventorySchedule $schedule, ?string $tenantSlug = null): array
    {
        $completed = $schedule->isCompleted();

        return [
            'id' => $schedule->id,
            'code' => $schedule->code,
            'title' => $schedule->title,
            'is_open' => $schedule->is_open,
            'is_completed' => $completed,
            'inventory_ids' => $schedule->inventories->pluck('id')->all(),
            'location_labels' => $schedule->location_labels,
            'location_label' => $schedule->location_label,
            // El enlace deja de compartirse cuando el QR ya fue usado.
            'public_url' => $completed ? null : $schedule->publicUrl($tenantSlug),
        ];
    }

    /**
     * Ejecuta la lectura en la sede indicada con `?tenant=slug` cuando la pide
     * un super administrador desde el portal; si no, en la sede activa.
     */
    private function runForRequestedTenant(Request $request, callable $callback): mixed
    {
        if (! $request->filled('tenant') || ! $request->user()?->isGlobalAdmin()) {
            return $callback(null);
        }

        $tenant = Tenant::query()
            ->where('slug', $request->query('tenant'))
            ->where('is_active', true)
            ->with('branding')
            ->firstOrFail();

        return app(TenantConnectionManager::class)->runForTenant($tenant, $callback);
    }

    /**
     * Consulta en cada sede activa las programaciones ya diligenciadas.
     */
    private function getCompletedSchedulesBySedeForPortal(): Collection
    {
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->with('branding')
            ->orderBy('id')
            ->get();

        $tenantConnections = app(TenantConnectionManager::class);

        return $tenants->map(function (Tenant $tenant) use ($tenantConnections): array {
            return $tenantConnections->runForTenant($tenant, function (Tenant $tenant): array {
                try {
                    // Las relaciones se cargan aqui: fuera del callback la
                    // conexion tenant ya no apunta a esta sede.
                    $schedules = InventorySchedule::query()
                        ->with(['inventories.group', 'entry.images'])
                        ->whereHas('entries')
                        ->get()
                        ->sortByDesc(fn (InventorySchedule $schedule) => $schedule->entry?->finished_at)
                        ->values();
                } catch (\Throwable $e) {
                    // Sede sin las tablas del modulo (migraciones pendientes).
                    $schedules = collect();
                }

                $sedeName = $this->resolveSedeName($tenant);

                return [
                    'tenant_id' => $tenant->id,
                    'tenant_slug' => $tenant->slug,
                    'sede_name' => $sedeName,
                    'dropdown_label' => "Mantenimientos sede {$sedeName}",
                    'timezone' => $tenant->branding?->timezone_value ?? 'America/Bogota',
                    'schedules' => $schedules,
                ];
            });
        });
    }

    private function resolveSedeName(Tenant $tenant): string
    {
        $rawName = trim((string) ($tenant->branding?->sede_name ?: $tenant->name ?: $tenant->slug));
        $normalized = preg_replace('/^sede\s+/iu', '', $rawName);

        return $normalized ?: ucfirst($tenant->slug);
    }
}
