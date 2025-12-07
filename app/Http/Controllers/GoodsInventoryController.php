<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AssetInventory;
use App\Models\AssetQuantity;
use App\Models\Inventory;
use App\Models\Asset;

class GoodsInventoryController extends Controller
{

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
            return view('inventories.goods-inventory', compact('inventory', 'assets'))
                ->renderSections()['content'];
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

        $inventoryId = $data['inventarioId'];
        $assetId     = $data['bien_id'];
        $tipo        = (int) $data['bien_tipo'];

        if ($tipo === 1) {
            $id = $this->handleCantidadType($inventoryId, $assetId);

        } elseif ($tipo === 2) {
            $id = $this->handleSerialType($inventoryId, $assetId);
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






    // =====================================================
    //  INTERNAL ORIGINAL LOGIC PORTED TO LARAVEL
    // =====================================================

    private function handleCantidadType($inventoryId, $assetId)
    {
        // Buscar relación asset_inventory
        $existing = DB::table('asset_inventory')
            ->where('asset_id', $assetId)
            ->where('inventory_id', $inventoryId)
            ->first();

        if (!$existing) {
            $id = DB::table('asset_inventory')->insertGetId([
                'asset_id'     => $assetId,
                'inventory_id' => $inventoryId
            ]);
        } else {
            $id = $existing->id;
        }

        // Insertar o sumar cantidad
        DB::table('asset_quantities')->updateOrInsert(
            ['asset_inventory_id' => $id],
            ['quantity' => DB::raw('quantity + 1')]
        );

        return $id;
    }


    private function handleSerialType($inventoryId, $assetId)
    {
        return DB::table('asset_inventory')->insertGetId([
            'asset_id'     => $assetId,
            'inventory_id' => $inventoryId
        ]);
    }
}
