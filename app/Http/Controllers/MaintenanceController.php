<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    public function index(int $inventoryId, int $assetId): JsonResponse
    {
        $maintenances = Maintenance::with('registeredBy:id,name')
            ->where('asset_id', $assetId)
            ->orderByDesc('date')
            ->get()
            ->map(fn ($m) => [
                'id'             => $m->id,
                'title'          => $m->title,
                'description'    => $m->description,
                'date'           => $m->date->format('Y-m-d'),
                'date_formatted' => $m->date->translatedFormat('d \d\e F \d\e Y'),
                'registered_by'  => $m->registeredBy?->name ?? '—',
            ]);

        return response()->json($maintenances);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'inventory_id' => ['required', 'integer', 'exists:inventories,id'],
            'asset_id'     => ['required', 'integer', 'exists:assets,id'],
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'date'         => ['required', 'date'],
        ]);

        $maintenance = Maintenance::create([
            'asset_id'      => $data['asset_id'],
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'date'          => $data['date'],
            'registered_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mantenimiento registrado correctamente.',
            'data'    => [
                'id'             => $maintenance->id,
                'title'          => $maintenance->title,
                'description'    => $maintenance->description,
                'date'           => $maintenance->date->format('Y-m-d'),
                'date_formatted' => $maintenance->date->translatedFormat('d \d\e F \d\e Y'),
                'registered_by'  => Auth::user()->name,
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return response()->json(['success' => true, 'message' => 'Mantenimiento eliminado.']);
    }
}
