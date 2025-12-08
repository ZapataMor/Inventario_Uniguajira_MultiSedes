<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Services\GoodsInventoryService;

class GoodsInventoryController extends Controller
{
    protected $service;

    public function __construct(GoodsInventoryService $service)
    {
        $this->service = $service;
        // middleware('auth') si lo necesitas
    }

    // ------------------------------
    // 3. Bienes dentro de un inventario
    // ------------------------------
    public function goodsIndex(Request $request, $groupId, $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        // Si inventory->group_id no coincide con $groupId, lanzar 404
        if ($inventory->group_id != $groupId) {
            abort(404);
        }
        // dd($inventory);
        $assets = DB::table('inventory_goods_view')
            ->where('inventory_id', $inventoryId)
            ->get();

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('inventories.goods-inventory', compact('inventory', 'assets'));
            return $view->renderSections()['content'];
        }

        return view('inventories.goods-inventory', compact('inventory', 'assets'));
    }


    /**
     * Crear bien dentro de un inventario
     * POST /api/goods-inventory/create
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'inventarioId' => 'required|integer|exists:inventories,id',
            'bien_id'      => 'required|integer|exists:assets,id',
            'bien_tipo'    => 'required|in:1,2', // 1=cantidad, 2=serial
        ]);

        $tipo = $data['bien_tipo'];

        if ($tipo == 1) {
            $id = $this->handleCantidadType($request);
        } elseif ($tipo == 2) {
            $id = $this->handleSerialType($request);
        }

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => "No se pudo agregar el bien."
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Bien agregado exitosamente.",
            'id'      => $id
        ]);
    }


    /**
     * Manejar bienes tipo cantidad
     */
    private function handleCantidadType(Request $request)
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1'
        ]);

        return $this->service->addQuantity(
            $request->inventarioId,
            $request->bien_id,
            $validated['cantidad']
        );
    }


    /**
     * Manejar bienes tipo serial
     */
    private function handleSerialType(Request $request)
    {
        $validated = $request->validate([
            'serial' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'condiciones_tecnicas' => 'nullable|string|max:500',
            'fecha_ingreso' => 'nullable|date',
        ]);

        $details = [
            'description' => $validated['descripcion'] ?? '',
            'brand' => $validated['marca'] ?? '',
            'model' => $validated['modelo'] ?? '',
            'serial' => $validated['serial'],
            'state' => $validated['estado'] ?? 'activo',
            'color' => $validated['color'] ?? '',
            'technical_conditions' => $validated['condiciones_tecnicas'] ?? '',
            'entry_date' => $validated['fecha_ingreso'] ?? now()->toDateString()
        ];

        return $this->service->addSerial(
            $request->inventarioId,
            $request->bien_id,
            $details
        );
    }


    /**
     * Actualizar cantidad de un bien
     * POST /api/goods-inventory/update-quantity
     */
    public function updateQuantity(Request $request)
    {
        $data = $request->validate([
            'bienId'       => 'required|integer|exists:assets,id',
            'inventarioId' => 'required|integer|exists:inventories,id',
            'cantidad'     => 'required|integer|min:0',
        ]);

        $assetId     = $data['bienId'];
        $inventoryId = $data['inventarioId'];
        $cantidad    = $data['cantidad'];

        $bienInventario = DB::table('asset_inventory')
            ->where('asset_id', $assetId)
            ->where('inventory_id', $inventoryId)
            ->first();

        if (!$bienInventario) {
            return response()->json([
                'success' => false,
                'message' => 'El bien no existe en este inventario.'
            ], 400);
        }

        $updated = DB::table('asset_quantities')
            ->where('asset_inventory_id', $bienInventario->id)
            ->update(['quantity' => $cantidad]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la cantidad.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cantidad actualizada exitosamente.'
        ]);
    }



    /**
     * Eliminar bien tipo cantidad
     * DELETE /api/goods-inventory/delete-quantity/{inventoryId}/{goodId}
     */
    public function deleteQuantity($inventoryId, $goodId)
    {
        if (!$inventoryId || !$goodId) {
            return response()->json([
                'success' => false,
                'message' => "ID requeridos."
            ], 400);
        }

        // buscar relación asset_inventory
        $rel = DB::table('asset_inventory as ai')
            ->join('assets as a', 'ai.asset_id', '=', 'a.id')
            ->where('ai.inventory_id', $inventoryId)
            ->where('ai.asset_id', $goodId)
            ->where('a.type', 'Cantidad')
            ->first();

        if (!$rel) {
            return response()->json([
                'success' => false,
                'message' => 'El bien no es tipo cantidad o no existe en este inventario.'
            ], 400);
        }

        // eliminar relación
        DB::table('asset_inventory')
            ->where('inventory_id', $inventoryId)
            ->where('asset_id', $goodId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado del inventario exitosamente.'
        ]);
    }

}
