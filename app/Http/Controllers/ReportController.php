<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFolder;
use App\Services\Reports\SimplePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(private readonly SimplePdfService $pdfService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $folders = ReportFolder::withCount('reports')
            ->orderByDesc('created_at')
            ->get();

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('reports.folders.index', compact('folders'));
            return $view->renderSections()['content'];
        }

        return view('reports.folders.index', compact('folders'));
    }

    public function getAll(int $folderId)
    {
        $folder = ReportFolder::findOrFail($folderId);
        $reports = $folder->reports()
            ->orderByDesc('created_at')
            ->get();

        return view('reports.folders.reports-list', compact('folder', 'reports'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'required|exists:report_folders,id',
            'nombreReporte' => 'required|string|max:255',
            'tipoReporte' => 'required|in:inventario,grupo,allInventories,goods,serial,removedGoods',
            'grupo_id' => 'nullable|required_if:tipoReporte,inventario,grupo|integer|exists:groups,id',
            'inventario_id' => 'nullable|required_if:tipoReporte,inventario|integer|exists:inventories,id',
        ]);

        try {
            $pdfPayload = $this->buildStyledPayload($validated);
            $html = view($pdfPayload['view'], $pdfPayload['data'])->render();

            $safeName = Str::slug($validated['nombreReporte'], '_');
            if ($safeName === '') {
                $safeName = 'reporte';
            }

            $relativePath = 'reports/' . now()->format('Y/m') . '/' . $safeName . '_' . now()->format('Ymd_His') . '.pdf';

            $pdfContent = $this->pdfService->buildHtml(
                $html,
                $pdfPayload['paper'],
                $pdfPayload['orientation']
            );

            Storage::disk('local')->put($relativePath, $pdfContent);

            $report = Report::create([
                'name' => trim($validated['nombreReporte']),
                'folder_id' => (int) $validated['folder_id'],
                'path' => $relativePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reporte generado exitosamente.',
                'report' => $report,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error generando reporte', [
                'message' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al generar el reporte.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rename(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
            'nombre' => 'required|string|max:255',
        ]);

        $report = Report::findOrFail((int) $request->input('report_id'));
        $report->update(['name' => trim((string) $request->input('nombre'))]);

        return response()->json([
            'success' => true,
            'message' => 'Reporte actualizado exitosamente.',
        ]);
    }

    public function destroy(int $id)
    {
        $report = Report::findOrFail($id);

        try {
            if ($report->path && Storage::exists($report->path)) {
                Storage::delete($report->path);
            } else {
                $possible = [
                    public_path($report->path ?? ''),
                    storage_path('app/' . ($report->path ?? '')),
                    base_path($report->path ?? ''),
                    $report->path ?? '',
                ];

                foreach ($possible as $path) {
                    if ($path && file_exists($path)) {
                        @unlink($path);
                        break;
                    }
                }
            }

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error eliminando reporte', [
                'report_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el reporte.',
            ], 500);
        }
    }

    public function download(Request $request)
    {
        $request->validate([
            'report_id' => 'required|exists:reports,id',
        ]);

        $report = Report::findOrFail((int) $request->input('report_id'));
        $filePath = $report->path;

        if (!$filePath) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta de reporte no disponible.',
            ], 404);
        }

        if (Storage::exists($filePath)) {
            return Storage::download($filePath, $this->sanitizeFileName($report->name) . '.pdf');
        }

        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            return response()->download($publicPath, $this->sanitizeFileName($report->name) . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (file_exists($filePath)) {
            return response()->download($filePath, $this->sanitizeFileName($report->name) . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado en el servidor.',
        ], 404);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{view: string, data: array<string, mixed>, paper: string, orientation: string}
     */
    private function buildStyledPayload(array $validated): array
    {
        $common = [
            'date' => now()->setTimezone('America/Bogota')->format('d/m/Y'),
            'logoDataUri' => $this->logoDataUri(),
        ];

        return match ((string) $validated['tipoReporte']) {
            'inventario' => [
                'view' => 'reports.pdf.reporte_de_un_inventario',
                'data' => array_merge($common, $this->inventoryData((int) $validated['grupo_id'], (int) $validated['inventario_id'])),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            'grupo' => [
                'view' => 'reports.pdf.reporte_de_un_grupo',
                'data' => array_merge($common, $this->groupData((int) $validated['grupo_id'])),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            'allInventories' => [
                'view' => 'reports.pdf.reporte_de_todos_los_inventarios',
                'data' => array_merge($common, $this->allInventoriesData()),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            'goods' => [
                'view' => 'reports.pdf.reporte_de_bienes',
                'data' => array_merge($common, $this->goodsData()),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            'serial' => [
                'view' => 'reports.pdf.reporte_de_equipos',
                'data' => array_merge($common, $this->serialGoodsData()),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            'removedGoods' => [
                'view' => 'reports.pdf.reporte_de_dados_de_baja',
                'data' => array_merge($common, $this->removedGoodsData()),
                'paper' => 'A4',
                'orientation' => 'portrait',
            ],
            default => throw ValidationException::withMessages([
                'tipoReporte' => 'Tipo de reporte no soportado.',
            ]),
        };
    }

    /**
     * @return array{inventory: object, goods: Collection<int, object>}
     */
    private function inventoryData(int $groupId, int $inventoryId): array
    {
        $inventory = DB::table('inventories as i')
            ->join('groups as g', 'g.id', '=', 'i.group_id')
            ->select(
                'i.id',
                'i.group_id',
                'i.name as nombre',
                'i.conservation_status as estado_conservacion',
                'g.name as grupo'
            )
            ->where('i.id', $inventoryId)
            ->first();

        if (!$inventory || (int) $inventory->group_id !== $groupId) {
            throw ValidationException::withMessages([
                'inventario_id' => 'El inventario no pertenece al grupo seleccionado.',
            ]);
        }

        $inventory->estado_conservacion = $this->mapConservationStatus((string) $inventory->estado_conservacion);

        $goods = DB::table('inventory_goods_view')
            ->where('inventory_id', $inventoryId)
            ->select('asset as bien', 'type as tipo', 'quantity as cantidad')
            ->orderBy('asset')
            ->get();

        return compact('inventory', 'goods');
    }

    /**
     * @return array{group: object, inventories: Collection<int, object>}
     */
    private function groupData(int $groupId): array
    {
        $group = DB::table('groups')
            ->select('id', 'name as nombre')
            ->where('id', $groupId)
            ->first();

        if (!$group) {
            throw ValidationException::withMessages([
                'grupo_id' => 'El grupo seleccionado no existe.',
            ]);
        }

        $inventories = DB::table('inventories')
            ->where('group_id', $groupId)
            ->orderBy('name')
            ->get()
            ->map(function (object $inventory): object {
                $inventory->estado_conservacion = $this->mapConservationStatus((string) $inventory->conservation_status);
                $inventory->nombre = $inventory->name;
                $inventory->goods = DB::table('inventory_goods_view')
                    ->where('inventory_id', $inventory->id)
                    ->select('asset as bien', 'type as tipo', 'quantity as cantidad')
                    ->orderBy('asset')
                    ->get();
                return $inventory;
            });

        return compact('group', 'inventories');
    }

    /**
     * @return array{groups: Collection<int, object>, totalGroups: int, totalInventories: int, totalGoods: int, removedByQuantity: Collection<int, object>, removedBySerial: Collection<int, object>}
     */
    private function allInventoriesData(): array
    {
        $groups = DB::table('groups')
            ->select('id', 'name as nombre')
            ->orderBy('name')
            ->get()
            ->map(function (object $group): object {
                $group->inventories = DB::table('inventories')
                    ->where('group_id', $group->id)
                    ->orderBy('name')
                    ->get()
                    ->map(function (object $inventory): object {
                        $inventory->nombre = $inventory->name;
                        $inventory->estado_conservacion = $this->mapConservationStatus((string) $inventory->conservation_status);
                        $inventory->fecha_modificacion = optional($inventory->updated_at)
                            ? date('d/m/Y', strtotime((string) $inventory->updated_at))
                            : now()->format('d/m/Y');
                        $inventory->goods = DB::table('inventory_goods_view')
                            ->where('inventory_id', $inventory->id)
                            ->select('asset as bien', 'type as tipo', 'quantity as cantidad')
                            ->orderBy('asset')
                            ->get();
                        return $inventory;
                    });
                return $group;
            });

        $totalGroups = $groups->count();
        $totalInventories = $groups->sum(fn (object $group): int => $group->inventories->count());
        $totalGoods = $groups->sum(function (object $group): int {
            return $group->inventories->sum(fn (object $inventory): int => $inventory->goods->count());
        });

        $removed = $this->removedGoodsData();
        $removedByQuantity = $removed['removedByQuantity'];
        $removedBySerial = $removed['removedBySerial'];

        return compact(
            'groups',
            'totalGroups',
            'totalInventories',
            'totalGoods',
            'removedByQuantity',
            'removedBySerial'
        );
    }

    /**
     * @return array{goods: Collection<int, object>}
     */
    private function goodsData(): array
    {
        $goods = DB::table('assets_summary_view')
            ->select('name as bien', 'type as tipo_bien', 'total_quantity as total_cantidad')
            ->orderBy('name')
            ->get();

        return compact('goods');
    }

    /**
     * @return array{groupedGoods: Collection<int, array{bien: string, items: Collection<int, object>}>}
     */
    private function serialGoodsData(): array
    {
        $serialGoods = DB::table('serial_goods_view')
            ->select(
                'asset as bien',
                'description as descripcion',
                'brand as marca',
                'model as modelo',
                'serial',
                'inventory as nombre_inventario',
                'status as estado',
                'technical_conditions as condiciones_tecnicas'
            )
            ->orderBy('asset')
            ->orderBy('serial')
            ->get();

        $groupedGoods = $serialGoods
            ->groupBy('bien')
            ->map(function (Collection $items, string $bien): array {
                return ['bien' => $bien, 'items' => $items];
            })
            ->values();

        return compact('groupedGoods');
    }

    /**
     * @return array{removedByQuantity: Collection<int, object>, removedBySerial: Collection<int, object>, totalRemoved: int, totalRemovedUnits: int}
     */
    private function removedGoodsData(): array
    {
        $removedByQuantity = DB::table('assets_removed as ar')
            ->join('inventories as i', 'i.id', '=', 'ar.inventory_id')
            ->join('groups as g', 'g.id', '=', 'i.group_id')
            ->leftJoin('users as u', 'u.id', '=', 'ar.user_id')
            ->select(
                'ar.name as bien',
                'ar.type as tipo',
                'ar.quantity as cantidad',
                'ar.reason as motivo',
                'g.name as grupo',
                'i.name as inventario',
                'u.name as usuario',
                'ar.created_at as fecha_baja'
            )
            ->orderByDesc('ar.created_at')
            ->get();

        $removedBySerial = DB::table('asset_equipments_removed as aer')
            ->join('inventories as i', 'i.id', '=', 'aer.inventory_id')
            ->join('groups as g', 'g.id', '=', 'i.group_id')
            ->leftJoin('users as u', 'u.id', '=', 'aer.user_id')
            ->select(
                'aer.name as bien',
                DB::raw("'Serial' as tipo"),
                DB::raw('1 as cantidad'),
                'aer.serial',
                'aer.brand as marca',
                'aer.model as modelo',
                'aer.status as estado',
                'aer.reason as motivo',
                'g.name as grupo',
                'i.name as inventario',
                'u.name as usuario',
                'aer.created_at as fecha_baja'
            )
            ->orderByDesc('aer.created_at')
            ->get();

        $totalRemoved = $removedByQuantity->count() + $removedBySerial->count();
        $totalRemovedUnits = (int) $removedByQuantity->sum('cantidad') + $removedBySerial->count();

        return compact('removedByQuantity', 'removedBySerial', 'totalRemoved', 'totalRemovedUnits');
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('assets/images/logoUniguajira.png');

        if (!file_exists($path)) {
            return null;
        }

        $imageData = file_get_contents($path);
        if ($imageData === false) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'jpg' || $extension === 'jpeg'
            ? 'image/jpeg'
            : ($extension === 'webp' ? 'image/webp' : 'image/png');

        return 'data:' . $mime . ';base64,' . base64_encode($imageData);
    }

    private function mapConservationStatus(string $status): string
    {
        return match ($status) {
            'good' => 'Bueno',
            'regular' => 'Regular',
            'bad' => 'Malo',
            default => 'No definido',
        };
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9\s\-_.]/', '', $name);
        $name = preg_replace('/\s+/', '_', (string) $name);
        $name = trim((string) $name, '_');

        return $name !== '' ? $name : 'reporte_' . now()->format('Y-m-d_H-i-s');
    }
}
