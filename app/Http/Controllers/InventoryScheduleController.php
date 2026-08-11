<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Inventory;
use App\Models\InventorySchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Modulo "Programacion de inventarios".
 *
 * Una programacion solo guarda el nombre con el que se identifica la labor
 * y, opcionalmente, las ubicaciones donde se hara. A partir de ahi se genera
 * el codigo publico que alimenta el QR y el enlace del formulario externo.
 * Las labores documentadas por personas externas entran por
 * PublicScheduleController.
 *
 * El QR es de un solo uso: cuando alguien diligencia el formulario, la
 * programacion queda cerrada y la tarjeta muestra lo documentado en lugar
 * del QR. Para una nueva labor se crea otra programacion.
 */
class InventoryScheduleController extends Controller
{
    /**
     * Crear, editar y eliminar programaciones queda restringido
     * a administradores de sede y super administradores.
     */
    private function autorizarGestion(): void
    {
        $user = Auth::user();

        abort_if(! $user || (! $user->isAdministrator() && ! $user->isSuperAdmin()), 403);
    }

    /**
     * Listado principal. Responde vista completa o solo la seccion
     * `content` cuando la peticion llega por AJAX (navegacion SPA).
     */
    public function index(Request $request)
    {
        // Modulo operativo: siempre vive dentro de una sede, nunca en el portal central.
        if (! tenant()) {
            return redirect()->route('portal.index');
        }

        $search = trim((string) $request->input('search', ''));

        $schedules = InventorySchedule::query()
            ->with(['inventories.group', 'entry'])
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->get();

        $inventories = Inventory::with('group:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'group_id']);

        $filters = ['search' => $search];

        $data = compact('schedules', 'inventories', 'filters');

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('schedules.index', $data);

            return $view->renderSections()['content'];
        }

        return view('schedules.index', $data);
    }

    /**
     * POST /api/schedules/create
     */
    public function store(Request $request): JsonResponse
    {
        $this->autorizarGestion();

        $data = $this->validateSchedule($request);

        $schedule = InventorySchedule::create([
            'title' => $data['title'],
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

        $oldValues = [
            'title' => $schedule->title,
            'inventory_ids' => $schedule->inventories->pluck('id')->all(),
        ];

        $schedule->update(['title' => $data['title']]);
        $schedule->inventories()->sync($data['inventory_ids']);

        ActivityLogger::updated(
            InventorySchedule::class,
            $schedule->id,
            $schedule->title,
            $oldValues,
            [
                'title' => $schedule->title,
                'inventory_ids' => $data['inventory_ids'],
            ]
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
     * Labores documentadas desde el formulario publico.
     */
    public function entries(int $id): JsonResponse
    {
        $schedule = InventorySchedule::with('entries')->findOrFail($id);

        return response()->json([
            'success' => true,
            'schedule' => $this->formatSchedule($schedule),
            'entries' => $schedule->entries->map(fn ($entry) => [
                'id' => $entry->id,
                'work_name' => $entry->work_name,
                'description' => $entry->description,
                'responsible_name' => $entry->responsible_name,
                'started_at' => $entry->started_at?->format('d/m/Y H:i'),
                'finished_at' => $entry->finished_at?->format('d/m/Y H:i'),
                'duration' => $entry->duration_label,
                'registered_at' => $entry->created_at?->format('d/m/Y H:i'),
            ])->values(),
        ]);
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
     */
    private function validateSchedule(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'inventory_ids' => ['nullable', 'array'],
            'inventory_ids.*' => ['integer', 'exists:inventories,id'],
        ], [
            'title.required' => 'El nombre de la programación es obligatorio.',
            'inventory_ids.*.exists' => 'Alguna de las ubicaciones seleccionadas ya no existe.',
        ]);

        $data['inventory_ids'] = array_values(array_unique($data['inventory_ids'] ?? []));

        return $data;
    }

    /**
     * Estructura JSON compartida por las respuestas del modulo.
     */
    private function formatSchedule(InventorySchedule $schedule): array
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
            'public_url' => $completed ? null : $schedule->publicUrl(),
        ];
    }
}
