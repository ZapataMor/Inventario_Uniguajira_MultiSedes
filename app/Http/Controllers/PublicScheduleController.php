<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\InventorySchedule;
use App\Models\InventoryScheduleEntry;
use App\Models\InventoryScheduleEntryImage;
use App\Services\Schedules\ScheduleEvidenceService;
use App\Services\Schedules\ScheduleReceiptService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Formulario publico de la "Programacion de inventarios".
 *
 * Se accede sin iniciar sesion, escaneando el QR o abriendo el enlace.
 * El slug de la sede viaja en la URL para poder activar la conexion
 * tenant correcta sin depender de la sesion del visitante.
 *
 * Alcance deliberadamente minimo: solo permite crear una labor sobre
 * una programacion abierta, adjuntarle evidencias fotograficas y
 * descargar el comprobante resultante. No expone ni modifica bienes,
 * inventarios ni usuarios.
 *
 * El formulario es de un solo uso: al enviarlo, la programacion queda
 * cerrada y el QR deja de admitir registros. Cada labor nueva necesita
 * su propia programacion con su propio QR.
 */
class PublicScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleEvidenceService $evidence,
        private readonly ScheduleReceiptService $receipts,
    ) {}

    /**
     * GET /programacion/{tenantSlug}/{code}
     */
    public function show(string $tenantSlug, string $code)
    {
        [$tenant, $schedule] = $this->resolve($tenantSlug, $code);

        $entry = $schedule->entry;
        $entry?->loadMissing('images');
        // El folio del comprobante se deriva del codigo de la programacion.
        $entry?->setRelation('schedule', $schedule);

        return view('schedules.public.form', [
            'tenant' => $tenant,
            'branding' => $tenant->branding,
            'schedule' => $schedule,
            'entry' => $entry,
            'submitted' => (bool) session('schedule_submitted'),
            'timezone' => $tenant->branding?->timezone_value ?? 'America/Bogota',
        ]);
    }

    /**
     * POST /programacion/{tenantSlug}/{code}
     */
    public function store(Request $request, string $tenantSlug, string $code): RedirectResponse
    {
        [, $schedule] = $this->resolve($tenantSlug, $code);

        // Un QR solo se diligencia una vez: el segundo envio se rechaza
        // aunque alguien conserve el enlace o reenvie el formulario.
        if ($schedule->isCompleted() || ! $schedule->is_open) {
            return back()->withErrors([
                'work_name' => 'Esta programación ya no admite nuevos registros.',
            ])->withInput();
        }

        $data = $request->validate([
            'work_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'responsible_name' => ['required', 'string', 'max:255'],
            'started_at' => ['required', 'date'],
            'finished_at' => ['required', 'date', 'after_or_equal:started_at'],
            // Las fotos ya viajaron una a una: aqui solo llegan sus tokens
            // y la descripcion opcional que escribio la persona.
            'images' => ['nullable', 'array'],
            'images.*.token' => ['nullable', 'string', 'max:80'],
            'images.*.description' => ['nullable', 'string', 'max:250'],
            'images.*.original_name' => ['nullable', 'string', 'max:240'],
        ], [
            'work_name.required' => 'Escribe el nombre del trabajo realizado.',
            'responsible_name.required' => 'Escribe el nombre del responsable.',
            'started_at.required' => 'Indica la fecha y hora en que iniciaste.',
            'finished_at.required' => 'Indica la fecha y hora en que terminaste.',
            'finished_at.after_or_equal' => 'La fecha de finalización no puede ser anterior a la de inicio.',
            'images.*.description.max' => 'Cada descripción de imagen admite máximo 250 caracteres.',
        ]);

        $entry = InventoryScheduleEntry::create([
            'inventory_schedule_id' => $schedule->id,
            'work_name' => $data['work_name'],
            'description' => $data['description'] ?? null,
            'responsible_name' => $data['responsible_name'],
            'started_at' => $data['started_at'],
            'finished_at' => $data['finished_at'],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $totalImages = $this->evidence->attach($entry, $schedule, $data['images'] ?? []);

        // El QR queda consumido: la programacion no vuelve a recibir labores.
        $schedule->forceFill(['is_open' => false])->save();

        ActivityLogger::custom(
            'create',
            "Registro externo en la programación: {$schedule->title} ({$data['work_name']})",
            [
                'model' => 'InventorySchedule',
                'model_id' => $schedule->id,
                'new_values' => ['evidencias' => $totalImages],
            ]
        );

        return back()->with('schedule_submitted', true);
    }

    /**
     * POST /programacion/{tenantSlug}/{code}/evidencias
     *
     * Recibe una sola foto y devuelve el token con el que quedara
     * enlazada al enviar el formulario. Subirlas de a una es lo que
     * permite adjuntar tantas como haga falta sin toparse con los
     * limites de tamano de peticion de PHP.
     */
    public function uploadEvidence(Request $request, string $tenantSlug, string $code): JsonResponse
    {
        [, $schedule] = $this->resolve($tenantSlug, $code);

        if ($schedule->isCompleted() || ! $schedule->is_open) {
            return response()->json([
                'success' => false,
                'message' => 'Esta programación ya no admite nuevos registros.',
            ], 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ], [
            'file.image' => 'El archivo seleccionado no es una imagen.',
            'file.mimes' => 'Formatos admitidos: JPG, PNG o WEBP.',
            'file.max' => 'Cada imagen puede pesar máximo 10 MB.',
        ]);

        $stored = $this->evidence->storeUpload($schedule, $request->file('file'));

        return response()->json([
            'success' => true,
            'token' => $stored['token'],
            'size' => $stored['size'],
            'original_name' => $stored['original_name'],
        ]);
    }

    /**
     * GET /programacion/{tenantSlug}/{code}/comprobante
     *
     * Descarga del comprobante por parte de quien diligencio el formulario.
     */
    public function receipt(string $tenantSlug, string $code): Response
    {
        [$tenant, $schedule] = $this->resolve($tenantSlug, $code);

        abort_if(! $schedule->isCompleted(), 404);

        return $this->receipts->download($schedule, $tenant);
    }

    /**
     * GET /programacion/{tenantSlug}/{code}/evidencias/{imageId}
     *
     * Sirve una evidencia ya registrada. El codigo de la programacion es
     * secreto, asi que solo la ve quien tiene el enlace o el QR original.
     */
    public function image(string $tenantSlug, string $code, int $imageId): BinaryFileResponse
    {
        [, $schedule] = $this->resolve($tenantSlug, $code);

        $image = InventoryScheduleEntryImage::with('entry')->find($imageId);

        abort_if(! $image || (int) $image->entry?->inventory_schedule_id !== (int) $schedule->id, 404);

        $path = $this->evidence->absolutePath($image);

        abort_if($path === null, 404);

        return response()->file($path, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Activa la sede indicada en la URL y devuelve la programacion.
     *
     * @return array{0: Tenant, 1: InventorySchedule}
     */
    private function resolve(string $tenantSlug, string $code): array
    {
        $tenant = Tenant::where('slug', $tenantSlug)
            ->where('is_active', true)
            ->first();

        abort_if(! $tenant, 404);

        app(TenantContext::class)->set($tenant);

        $schedule = InventorySchedule::where('code', $code)->first();

        abort_if(! $schedule, 404);

        return [$tenant, $schedule];
    }
}
