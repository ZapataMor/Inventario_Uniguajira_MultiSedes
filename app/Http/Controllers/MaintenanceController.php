<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\AssetEquipment;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * Historial de mantenimientos de un bien serial individual.
     * Scoped por equipment_id (persiste aunque el serial cambie de inventario).
     * GET /api/maintenances/equipment/{equipmentId}
     */
    public function indexByEquipment(int $equipmentId): JsonResponse
    {
        $maintenances = Maintenance::with('registeredBy:id,name')
            ->where('equipment_id', $equipmentId)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($m) => $this->formatMaintenance($m));

        return response()->json($maintenances);
    }

    /**
     * Historial de mantenimientos agrupado por bien en inventario.
     * Usado desde la vista de bienes (inventory_id + asset_id).
     * GET /api/maintenances/{inventoryId}/{assetId}
     */
    public function index(int $inventoryId, int $assetId): JsonResponse
    {
        $maintenances = Maintenance::with('registeredBy:id,name')
            ->where('inventory_id', $inventoryId)
            ->where('asset_id', $assetId)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($m) => $this->formatMaintenance($m));

        return response()->json($maintenances);
    }

    /**
     * Crear un mantenimiento.
     * Si se envía equipment_id → mantenimiento individual de serial.
     * Si se envía inventory_id + asset_id → mantenimiento grupal.
     * POST /api/maintenances/create
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'equipment_id' => ['nullable', 'integer', 'exists:asset_equipments,id'],
            'inventory_id' => ['nullable', 'integer', 'exists:inventories,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ]);

        $maintenance = Maintenance::create([
            'equipment_id' => $data['equipment_id'] ?? null,
            'inventory_id' => $data['inventory_id'] ?? null,
            'asset_id' => $data['asset_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'registered_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mantenimiento registrado correctamente.',
            'data' => $this->formatMaintenance($maintenance->load('registeredBy')),
        ]);
    }

    /**
     * Registrar un mismo mantenimiento sobre varios seriales a la vez.
     *
     * Se usa desde la vista de seriales de un inventario, donde todas las
     * tarjetas pertenecen al mismo bien. Por eso se exige que los equipos
     * seleccionados sean del mismo tipo: un mantenimiento masivo describe
     * una sola labor sobre un mismo modelo de bien.
     *
     * POST /api/maintenances/batch-create
     */
    public function batchStore(Request $request): JsonResponse
    {
        // Operacion masiva: se restringe a administradores, igual que el
        // formulario que la dispara en la interfaz.
        $user = Auth::user();
        abort_if(! $user || ! $user->isAdministrator(), 403);

        $data = $request->validate([
            'equipment_ids' => ['required', 'array', 'min:2'],
            'equipment_ids.*' => ['integer', 'distinct', 'exists:asset_equipments,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date'],
        ], [
            'equipment_ids.required' => 'Selecciona los bienes a los que se les registrará el mantenimiento.',
            'equipment_ids.min' => 'Selecciona al menos dos bienes para un mantenimiento masivo.',
            'title.required' => 'El título del mantenimiento es obligatorio.',
            'date.required' => 'La fecha del mantenimiento es obligatoria.',
        ]);

        $equipments = AssetEquipment::with('assetInventory')
            ->whereIn('id', $data['equipment_ids'])
            ->get();

        $assetIds = $equipments->pluck('assetInventory.asset_id')->unique();

        if ($assetIds->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Los bienes seleccionados deben ser del mismo tipo.',
            ], 422);
        }

        $now = now();

        $rows = $equipments->map(fn (AssetEquipment $equipment) => [
            'equipment_id' => $equipment->id,
            'asset_id' => $equipment->assetInventory?->asset_id,
            'inventory_id' => $equipment->assetInventory?->inventory_id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'date' => $data['date'],
            'registered_by' => Auth::id(),
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::transaction(fn () => Maintenance::insert($rows));

        $total = count($rows);
        $serials = $equipments->pluck('serial')->filter()->implode(', ');

        ActivityLogger::custom(
            'create',
            "Mantenimiento masivo \"{$data['title']}\" registrado en {$total} bienes seriales: {$serials}",
            [
                'model' => 'Maintenance',
                'model_id' => $assetIds->first(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Mantenimiento registrado en {$total} bienes.",
            'total' => $total,
        ]);
    }

    /**
     * DELETE /api/maintenances/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['success' => true, 'message' => 'Mantenimiento eliminado.']);
    }

    private function formatMaintenance(Maintenance $m): array
    {
        return [
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'date' => $m->date->format('Y-m-d'),
            'date_formatted' => $m->date->translatedFormat('d \d\e F \d\e Y'),
            'registered_by' => $m->registeredBy?->name ?? '—',
        ];
    }
}
