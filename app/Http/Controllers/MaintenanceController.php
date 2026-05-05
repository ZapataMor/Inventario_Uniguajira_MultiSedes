<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'asset_id'     => ['nullable', 'integer', 'exists:assets,id'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'date'         => ['required', 'date'],
        ]);

        $maintenance = Maintenance::create([
            'equipment_id' => $data['equipment_id'] ?? null,
            'inventory_id' => $data['inventory_id'] ?? null,
            'asset_id'     => $data['asset_id'] ?? null,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'date'         => $data['date'],
            'registered_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mantenimiento registrado correctamente.',
            'data'    => $this->formatMaintenance($maintenance->load('registeredBy')),
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
            'id'             => $m->id,
            'title'          => $m->title,
            'description'    => $m->description,
            'date'           => $m->date->format('Y-m-d'),
            'date_formatted' => $m->date->translatedFormat('d \d\e F \d\e Y'),
            'registered_by'  => $m->registeredBy?->name ?? '—',
        ];
    }
}
