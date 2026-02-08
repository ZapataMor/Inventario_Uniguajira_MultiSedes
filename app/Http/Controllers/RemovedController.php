<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AssetRemoved;

class RemovedController extends Controller
{
    /**
     * Display a listing of removed assets (goods)
     * GET /removed
     */
    public function index(Request $request)
    {
        // Obtener todos los bienes dados de baja con información relacionada
        $removedAssets = DB::table('assets_removed as ar')
            ->join('assets as a', 'ar.asset_id', '=', 'a.id')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.id',
                'ar.name as asset_name',
                'ar.type',
                'ar.image',
                'ar.quantity',
                'ar.reason',
                'ar.created_at as removed_at',
                'a.id as original_asset_id',
                'i.id as inventory_id',
                'i.name as inventory_name',
                'g.id as group_id',
                'g.name as group_name',
                'u.name as removed_by_user'
            )
            ->orderBy('ar.created_at', 'desc')
            ->get();

        // Agrupar estadísticas
        $stats = [
            'total_removed' => $removedAssets->count(),
            'total_quantity' => $removedAssets->sum('quantity'),
            'by_type' => [
                'cantidad' => $removedAssets->where('type', 'Cantidad')->count(),
                'serial' => $removedAssets->where('type', 'Serial')->count(),
            ],
            'recent_count' => $removedAssets->where('removed_at', '>=', now()->subDays(30))->count(),
        ];

        if ($request->ajax()) {
            // Si es una carga AJAX, solo renderiza el contenido interno
            return view('removed.goods-removed', compact('removedAssets', 'stats'))
                ->renderSections()['content'];
        }

        // Si es carga normal (primera vez), usa el layout completo
        return view('removed.goods-removed', compact('removedAssets', 'stats'));
    }

    /**
     * Show details of a specific removed asset
     * GET /removed/{id}
     */
    public function show(Request $request, $id)
    {
        $removedAsset = DB::table('assets_removed as ar')
            ->join('assets as a', 'ar.asset_id', '=', 'a.id')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.*',
                'a.id as original_asset_id',
                'i.name as inventory_name',
                'i.responsible as inventory_responsible',
                'g.name as group_name',
                'u.name as removed_by_user',
                'u.email as user_email'
            )
            ->where('ar.id', $id)
            ->first();

        if (!$removedAsset) {
            abort(404, 'Registro de baja no encontrado');
        }

        // Siempre devolver la vista parcial (para usar en modal)
        return view('removed.show', compact('removedAsset'));
    }

    /**
     * Get removed assets filtered by various criteria
     * GET /api/removed/filter
     */
    public function filter(Request $request)
    {
        $query = DB::table('assets_removed as ar')
            ->join('assets as a', 'ar.asset_id', '=', 'a.id')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.id',
                'ar.name as asset_name',
                'ar.type',
                'ar.quantity',
                'ar.reason',
                'ar.created_at as removed_at',
                'i.name as inventory_name',
                'g.name as group_name',
                'u.name as removed_by_user'
            );

        // Filtrar por tipo
        if ($request->has('type') && $request->type != 'all') {
            $query->where('ar.type', $request->type);
        }

        // Filtrar por grupo
        if ($request->has('group_id')) {
            $query->where('g.id', $request->group_id);
        }

        // Filtrar por inventario
        if ($request->has('inventory_id')) {
            $query->where('i.id', $request->inventory_id);
        }

        // Filtrar por rango de fechas
        if ($request->has('date_from')) {
            $query->where('ar.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('ar.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Búsqueda por nombre
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ar.name', 'like', "%{$search}%")
                  ->orWhere('ar.reason', 'like', "%{$search}%");
            });
        }

        $removedAssets = $query->orderBy('ar.created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $removedAssets,
            'count' => $removedAssets->count()
        ]);
    }

    /**
     * Export removed assets to Excel/CSV
     * GET /api/removed/export
     */
    public function export(Request $request)
    {
        $removedAssets = DB::table('assets_removed as ar')
            ->join('assets as a', 'ar.asset_id', '=', 'a.id')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.name as Bien',
                'ar.type as Tipo',
                'ar.quantity as Cantidad',
                'ar.reason as Motivo',
                'g.name as Grupo',
                'i.name as Inventario',
                'u.name as Usuario',
                'ar.created_at as Fecha_Baja'
            )
            ->orderBy('ar.created_at', 'desc')
            ->get();

        $filename = 'bienes_dados_de_baja_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($removedAssets) {
            $file = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, ['Bien', 'Tipo', 'Cantidad', 'Motivo', 'Grupo', 'Inventario', 'Usuario', 'Fecha de Baja']);
            
            // Data
            foreach ($removedAssets as $asset) {
                fputcsv($file, [
                    $asset->Bien,
                    $asset->Tipo,
                    $asset->Cantidad,
                    $asset->Motivo,
                    $asset->Grupo,
                    $asset->Inventario,
                    $asset->Usuario ?? 'N/A',
                    $asset->Fecha_Baja
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Delete a removed asset record (only for admins)
     * DELETE /api/removed/{id}
     */
    public function destroy($id)
    {
        $removed = AssetRemoved::find($id);

        if (!$removed) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado.'
            ], 404);
        }

        $removed->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado del historial de bajas.'
        ]);
    }

    /**
     * Get statistics for removed assets
     * GET /api/removed/stats
     */
    public function stats()
    {
        $stats = [
            'total_removed' => AssetRemoved::count(),
            'total_quantity' => AssetRemoved::sum('quantity'),
            'by_type' => [
                'cantidad' => AssetRemoved::where('type', 'Cantidad')->count(),
                'serial' => AssetRemoved::where('type', 'Serial')->count(),
            ],
            'by_month' => DB::table('assets_removed')
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(quantity) as total_quantity')
                )
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get(),
            'recent_30_days' => AssetRemoved::where('created_at', '>=', now()->subDays(30))->count(),
            'top_reasons' => DB::table('assets_removed')
                ->select('reason', DB::raw('COUNT(*) as count'))
                ->whereNotNull('reason')
                ->where('reason', '!=', '')
                ->groupBy('reason')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}