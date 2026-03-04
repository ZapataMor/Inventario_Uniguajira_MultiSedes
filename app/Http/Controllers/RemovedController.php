<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AssetRemoved;
use App\Helpers\ActivityLogger;

class RemovedController extends Controller
{
    /**
     * Display a listing of removed assets (goods)
     * GET /removed
     */
    public function index(Request $request)
    {
        // 1) Bienes removidos por CANTIDAD
        $removedByQuantity = DB::table('assets_removed as ar')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.id',
                DB::raw("'cantidad' as source"),
                'ar.name as asset_name',
                'ar.type',
                'ar.image',
                'ar.quantity',
                DB::raw("NULL as serial"),
                'ar.reason',
                'ar.created_at as removed_at',
                'ar.asset_id as original_asset_id',
                'i.id as inventory_id',
                'i.name as inventory_name',
                'g.id as group_id',
                'g.name as group_name',
                'u.name as removed_by_user'
            );

        // 2) Bienes removidos por SERIAL (asset_equipments_removed)
        $removedBySerial = DB::table('asset_equipments_removed as aer')
            ->join('inventories as i', 'aer.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'aer.user_id', '=', 'u.id')
            ->select(
                'aer.id',
                DB::raw("'serial' as source"),
                'aer.name as asset_name',
                DB::raw("'Serial' as type"),
                'aer.image',
                DB::raw("1 as quantity"),
                'aer.serial',
                'aer.reason',
                'aer.created_at as removed_at',
                'aer.asset_id as original_asset_id',
                'i.id as inventory_id',
                'i.name as inventory_name',
                'g.id as group_id',
                'g.name as group_name',
                'u.name as removed_by_user'
            );

        // 3) Unir ambos
        $removedAssets = $removedByQuantity
            ->unionAll($removedBySerial)
            ->orderBy('removed_at', 'desc')
            ->get();

        // Agrupar estadísticas
        $stats = [
            'total_removed'   => $removedAssets->count(),
            'total_quantity'  => $removedAssets->sum('quantity'),
            'by_type'         => [
                'cantidad' => $removedAssets->where('type', 'Cantidad')->count(),
                'serial'   => $removedAssets->where('type', 'Serial')->count(),
            ],
            'recent_count'    => $removedAssets->where('removed_at', '>=', now()->subDays(30))->count(),
        ];

        if ($request->ajax()) {
            return view('removed.goods-removed', compact('removedAssets', 'stats'))
                ->renderSections()['content'];
        }

        return view('removed.goods-removed', compact('removedAssets', 'stats'));
    }

    /**
     * Show details of a specific removed asset
     * GET /removed/{id}?source=cantidad|serial
     */
    public function show(Request $request, $id)
    {
        $source = $request->query('source', 'cantidad');

        if ($source === 'serial') {
            $removedAsset = DB::table('asset_equipments_removed as aer')
                ->join('inventories as i', 'aer.inventory_id', '=', 'i.id')
                ->join('groups as g', 'i.group_id', '=', 'g.id')
                ->leftJoin('users as u', 'aer.user_id', '=', 'u.id')
                ->select(
                    'aer.*',
                    'aer.asset_id as original_asset_id',
                    'i.name as inventory_name',
                    'i.responsible as inventory_responsible',
                    'g.name as group_name',
                    'u.name as removed_by_user',
                    'u.email as user_email',
                    DB::raw("'Serial' as type"),
                    DB::raw("1 as quantity")
                )
                ->where('aer.id', $id)
                ->first();
        } else {
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
        }

        if (!$removedAsset) {
            abort(404, 'Registro de baja no encontrado');
        }

        return view('components.modal.removed.show', compact('removedAsset'));
    }

    /**
     * Get removed assets filtered by various criteria
     * GET /api/removed/filter
     */
    public function filter(Request $request)
    {
        $queryQuantity = DB::table('assets_removed as ar')
            ->join('inventories as i', 'ar.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'ar.user_id', '=', 'u.id')
            ->select(
                'ar.id',
                DB::raw("'cantidad' as source"),
                'ar.name as asset_name',
                'ar.type',
                'ar.image',
                'ar.quantity',
                DB::raw("NULL as serial"),
                'ar.reason',
                'ar.created_at as removed_at',
                'ar.asset_id as original_asset_id',
                'i.id as inventory_id',
                'i.name as inventory_name',
                'g.id as group_id',
                'g.name as group_name',
                'u.name as removed_by_user'
            );

        $querySerial = DB::table('asset_equipments_removed as aer')
            ->join('inventories as i', 'aer.inventory_id', '=', 'i.id')
            ->join('groups as g', 'i.group_id', '=', 'g.id')
            ->leftJoin('users as u', 'aer.user_id', '=', 'u.id')
            ->select(
                'aer.id',
                DB::raw("'serial' as source"),
                'aer.name as asset_name',
                DB::raw("'Serial' as type"),
                'aer.image',
                DB::raw("1 as quantity"),
                'aer.serial',
                'aer.reason',
                'aer.created_at as removed_at',
                'aer.asset_id as original_asset_id',
                'i.id as inventory_id',
                'i.name as inventory_name',
                'g.id as group_id',
                'g.name as group_name',
                'u.name as removed_by_user'
            );

        // Filtro por tipo
        if ($request->has('type') && $request->type != 'all') {
            if ($request->type == 'Cantidad') {
                $querySerial = null;
            } elseif ($request->type == 'Serial') {
                $queryQuantity = null;
            }
        }

        // Filtro por grupo
        if ($request->has('group_id') && $request->group_id != '') {
            $queryQuantity?->where('g.id', $request->group_id);
            $querySerial?->where('g.id', $request->group_id);
        }

        // Filtro por inventario
        if ($request->has('inventory_id') && $request->inventory_id != '') {
            $queryQuantity?->where('i.id', $request->inventory_id);
            $querySerial?->where('i.id', $request->inventory_id);
        }

        // Filtro por fechas
        if ($request->has('date_from') && $request->date_from != '') {
            $queryQuantity?->where('ar.created_at', '>=', $request->date_from);
            $querySerial?->where('aer.created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $queryQuantity?->where('ar.created_at', '<=', $request->date_to . ' 23:59:59');
            $querySerial?->where('aer.created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Búsqueda por nombre
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $queryQuantity?->where(function ($q) use ($search) {
                $q->where('ar.name', 'like', "%{$search}%")
                  ->orWhere('ar.reason', 'like', "%{$search}%");
            });
            $querySerial?->where(function ($q) use ($search) {
                $q->where('aer.name', 'like', "%{$search}%")
                  ->orWhere('aer.reason', 'like', "%{$search}%");
            });
        }

        if ($queryQuantity && $querySerial) {
            $removedAssets = $queryQuantity->unionAll($querySerial)->orderBy('removed_at', 'desc')->get();
        } elseif ($queryQuantity) {
            $removedAssets = $queryQuantity->orderBy('ar.created_at', 'desc')->get();
        } elseif ($querySerial) {
            $removedAssets = $querySerial->orderBy('aer.created_at', 'desc')->get();
        } else {
            $removedAssets = collect();
        }

        return response()->json([
            'success' => true,
            'data'    => $removedAssets,
            'count'   => $removedAssets->count(),
        ]);
    }

    /**
     * Get groups and inventories for filter dropdowns
     * GET /api/removed/filter-options
     */
    public function filterOptions()
    {
        $groups = DB::table('groups')->select('id', 'name')->orderBy('name')->get();

        $inventories = DB::table('inventories')
            ->join('groups', 'inventories.group_id', '=', 'groups.id')
            ->select('inventories.id', 'inventories.name', 'inventories.group_id', 'groups.name as group_name')
            ->orderBy('groups.name')
            ->orderBy('inventories.name')
            ->get();

        return response()->json([
            'success'      => true,
            'groups'       => $groups,
            'inventories'  => $inventories,
        ]);
    }

    /**
     * Export removed assets to CSV
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
                'ar.name as Bien', 'ar.type as Tipo', 'ar.quantity as Cantidad',
                'ar.reason as Motivo', 'g.name as Grupo', 'i.name as Inventario',
                'u.name as Usuario', 'ar.created_at as Fecha_Baja'
            )
            ->orderBy('ar.created_at', 'desc')
            ->get();

        $filename = 'bienes_dados_de_baja_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($removedAssets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, ['Bien', 'Tipo', 'Cantidad', 'Motivo', 'Grupo', 'Inventario', 'Usuario', 'Fecha de Baja']);

            foreach ($removedAssets as $asset) {
                fputcsv($file, [
                    $asset->Bien,
                    $asset->Tipo,
                    $asset->Cantidad,
                    $asset->Motivo,
                    $asset->Grupo,
                    $asset->Inventario,
                    $asset->Usuario ?? 'N/A',
                    $asset->Fecha_Baja,
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
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $assetName = $removed->name;

        $removed->delete();

        ActivityLogger::deleted(AssetRemoved::class, $id, $assetName);

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado del historial de bajas.',
        ]);
    }

    /**
     * Get statistics for removed assets
     * GET /api/removed/stats
     */
    public function stats()
    {
        $stats = [
            'total_removed'  => AssetRemoved::count(),
            'total_quantity' => AssetRemoved::sum('quantity'),
            'by_type'        => [
                'cantidad' => AssetRemoved::where('type', 'Cantidad')->count(),
                'serial'   => AssetRemoved::where('type', 'Serial')->count(),
            ],
            'by_month'       => DB::table('assets_removed')
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
            'top_reasons'    => DB::table('assets_removed')
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
            'data'    => $stats,
        ]);
    }
}