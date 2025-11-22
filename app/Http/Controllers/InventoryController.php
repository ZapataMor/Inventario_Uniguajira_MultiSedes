<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Inventory;
use App\Models\Asset;
use App\Models\AssetEquipment;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{

    // ------------------------------
    // 1. Mostrar TODOS LOS GRUPOS
    // ------------------------------
    public function groupIndex(Request $request)
    {
        $groups = Group::withCount('inventories')->get();

        if ($request->ajax()) {
            return view('inventories.groups', compact('groups'))
                ->renderSections()['content'];
        }

        return view('inventories.groups', compact('groups'));
    }

    // ------------------------------
    // 2. Inventarios de un grupo
    // ------------------------------
    public function inventoryIndex(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);

        $inventories = Inventory::where('group_id', $groupId)
            ->select('inventories.*')

            // === COUNT OF DISTINCT ASSETS ===
            ->selectRaw("
                (
                    SELECT COUNT(DISTINCT a.id)
                    FROM asset_inventory ai
                    LEFT JOIN assets a ON ai.asset_id = a.id
                    WHERE ai.inventory_id = inventories.id
                ) AS total_asset_types
            ")

            // === TOTAL AMOUNT = SUM(quantity) + COUNT(serials) ===
            ->selectRaw("
                (
                    SELECT
                        COALESCE((
                            SELECT SUM(aq.quantity)
                            FROM asset_quantities aq
                            WHERE aq.asset_inventory_id IN (
                                SELECT ai2.id
                                FROM asset_inventory ai2
                                WHERE ai2.inventory_id = inventories.id
                            )
                        ), 0)
                        +
                        COALESCE((
                            SELECT COUNT(ae.id)
                            FROM asset_equipments ae
                            WHERE ae.asset_inventory_id IN (
                                SELECT ai3.id
                                FROM asset_inventory ai3
                                WHERE ai3.inventory_id = inventories.id
                            )
                        ), 0)
                ) AS total_assets
            ")

            ->get();

        if ($request->ajax()) {
            return view('inventories.inventories', compact('group', 'inventories'))
                ->renderSections()['content'];
        }

        return view('inventories.inventories', compact('group', 'inventories'));
    }

    // ------------------------------
    // 3. Bienes dentro de un inventario
    // ------------------------------
    public function goodsIndex(Request $request, $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        $assets = DB::table('inventory_goods_view')
            ->where('inventory_id', $inventoryId)
            ->get();

        if ($request->ajax()) {
            return view('inventories.goods-inventory', compact('inventory', 'assets'))
                ->renderSections()['content'];
        }

        return view('inventories.goods-inventory', compact('inventory', 'assets'));
    }

    // -----------------------------------
    // 4. Bienes tipo serial (detalles)
    // -----------------------------------
    public function serialsIndex(Request $request, $inventoryId, $assetId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        $serials = DB::table('serial_goods_view')
            ->where('inventory_id', $inventoryId)
            ->where('asset_id', $assetId)
            ->get();

        if ($request->ajax()) {
            return view('inventories.serials-goods-inventory',
                compact('inventory', 'serials')
            )->renderSections()['content'];
        }

        return view('inventories.serials-goods-inventory',
            compact('inventory', 'serials')
        );
    }


    // ------------------------------
    // 5. Página principal Inventarios
    // ------------------------------
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return view('inventories.index')->renderSections()['content'];
        }

        return view('inventories.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
