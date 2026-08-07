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
 * y, opcionalmente, el inventario donde se hara. A partir de ahi se genera
 * el codigo publico que alimenta el QR y el enlace del formulario externo.
 * Las labores documentadas por personas externas entran por
 * PublicScheduleController.
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
            ->with(['inventory.group'])
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
            ...$data,
            'code' => InventorySchedule::generateCode(),
            'is_open' => true,
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::created(InventorySchedule::class, $schedule->id, $schedule->title);

        return response()->json([
            'success' => true,
            'message' => 'Programación creada correctamente.',
            'data' => $this->formatSchedule($schedule->fresh(['inventory.group'])),
        ]);
    }

    /**
     * POST /api/schedules/update
     */
    public function update(Request $request): JsonResponse
    {
        $this->autorizarGestion();

        $request->validate(['id' => ['required', 'integer', 'exists:inventory_schedules,id']]);

        $schedule = InventorySchedule::findOrFail($request->input('id'));
        $data = $this->validateSchedule($request);

        $oldValues = $schedule->only(['title', 'inventory_id']);

        $schedule->update($data);

        ActivityLogger::updated(
            InventorySchedule::class,
            $schedule->id,
            $schedule->title,
            $oldValues,
            $schedule->only(array_keys($oldValues))
        );

        return response()->json([
            'success' => true,
            'message' => 'Programación actualizada correctamente.',
            'data' => $this->formatSchedule($schedule->fresh(['inventory.group'])),
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
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
        ], [
            'title.required' => 'El nombre de la programación es obligatorio.',
        ]);
    }

    /**
     * Estructura JSON compartida por las respuestas del modulo.
     */
    private function formatSchedule(InventorySchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'code' => $schedule->code,
            'title' => $schedule->title,
            'is_open' => $schedule->is_open,
            'inventory_id' => $schedule->inventory_id,
            'location_label' => $schedule->location_label,
            'public_url' => $schedule->publicUrl(),
        ];
    }
}
