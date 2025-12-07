<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetInventory;
use Illuminate\Support\Facades\Storage;

class GoodsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Consultar la vista SQL
        $dataGoods = DB::table('assets_summary_view')->get();

        if ($request->ajax()) {
            // si es una carga AJAX, solo renderiza el contenido interno
            return view('goods.index', compact('dataGoods'))
                ->renderSections()['content'];
        }

        // si es carga normal (primera vez), usa el layout completo
        return view('goods.index', compact('dataGoods'));
    }

    /**
     * GET /api/goods/get/json
     * Devuelve todos los bienes con id, nombre y tipo (igual que la versión PHP)
     */
    public function getJson()
    {
        // Obtener todos los bienes desde la tabla `assets`
        $goods = Asset::select('id', 'name as bien', 'type as tipo')->get();

        return response()->json($goods);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo'   => 'required|integer|in:1,2',
            'imagen' => 'nullable|image|max:2048' // 2 MB
        ]);

        // Guardar imagen si existe
        $path = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('assets/goods', 'public');
        }

        $asset = Asset::create([
            'name'  => $request->nombre,
            'type'  => $request->tipo,
            'image' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bien creado exitosamente.',
            'assetId' => $asset->id
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer|exists:assets,id',
            'nombre' => 'required|string|max:255',
            'imagen' => 'nullable|image|max:2048'
        ]);

        $asset = Asset::findOrFail($request->id);

        // Procesar imagen si viene una nueva
        if ($request->hasFile('imagen')) {
            // Borrar imagen anterior
            if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                Storage::disk('public')->delete($asset->image);
            }

            // Guardar nueva
            $asset->image = $request->file('imagen')->store('assets/goods', 'public');
        }

        // Actualizar nombre
        $asset->name = $request->nombre;

        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Bien actualizado correctamente.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Bien no encontrado.'
            ], 404);
        }

        // Obtener cantidad total (igual que tu vista SQL)
        $total = AssetInventory::where('asset_id', $id)
            ->leftJoin('asset_quantities', 'asset_inventory.id', '=', 'asset_quantities.asset_inventory_id')
            ->leftJoin('asset_equipments', 'asset_inventory.id', '=', 'asset_equipments.asset_inventory_id')
            ->selectRaw("
                COALESCE(SUM(asset_quantities.quantity), 0) +
                COALESCE(COUNT(asset_equipments.id), 0) AS total
            ")
            ->value('total');

        if ($total > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el bien porque su cantidad es mayor a 0.'
            ], 400);
        }

        // Eliminar imagen
        if ($asset->image && Storage::disk('public')->exists($asset->image)) {
            Storage::disk('public')->delete($asset->image);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado correctamente.'
        ]);
    }
}
