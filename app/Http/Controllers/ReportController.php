<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportFolder;
use App\Services\Reports\SimplePdfService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $validated = $request->validate([
            'folder_id' => 'required|exists:report_folders,id',
            'nombreReporte' => 'required|string|max:255',
            'tipoReporte' => 'required|in:inventario,grupo,allInventories,goods,serial,removedGoods,historial',
            'grupo_id' => 'nullable|required_if:tipoReporte,inventario,grupo|integer|exists:groups,id',
            'inventario_id' => 'nullable|required_if:tipoReporte,inventario|integer|exists:inventories,id',
            'formato' => 'nullable|in:pdf,excel',
        ]);

        $formato = $validated['formato'] ?? 'pdf';

        try {
            $safeName = Str::slug($validated['nombreReporte'], '_');
            if ($safeName === '') {
                $safeName = 'reporte';
            }

            if ($formato === 'excel') {
                $spreadsheet = $this->buildExcelSpreadsheet($validated);
                $relativePath = tenant_asset('reports/'.now()->format('Y/m').'/'.$safeName.'_'.now()->format('Ymd_His').'.xlsx');

                $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
                (new Xlsx($spreadsheet))->save($tmpFile);
                Storage::disk('local')->put($relativePath, file_get_contents($tmpFile));
                @unlink($tmpFile);
            } else {
                $pdfPayload = $this->buildStyledPayload($validated);
                $html = view($pdfPayload['view'], $pdfPayload['data'])->render();
                $relativePath = tenant_asset('reports/'.now()->format('Y/m').'/'.$safeName.'_'.now()->format('Ymd_His').'.pdf');

                $pdfContent = $this->pdfService->buildHtml(
                    $html,
                    $pdfPayload['paper'],
                    $pdfPayload['orientation']
                );

                Storage::disk('local')->put($relativePath, $pdfContent);
            }

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
        abort_if(! auth()->user()?->isAdministrator(), 403);

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
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $report = Report::findOrFail($id);

        try {
            if ($report->path && Storage::exists($report->path)) {
                Storage::delete($report->path);
            } else {
                $possible = [
                    public_path($report->path ?? ''),
                    storage_path('app/'.($report->path ?? '')),
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

        if (! $filePath) {
            return response()->json([
                'success' => false,
                'message' => 'Ruta de reporte no disponible.',
            ], 404);
        }

        $isExcel = str_ends_with((string) $filePath, '.xlsx');
        $extension = $isExcel ? '.xlsx' : '.pdf';
        $contentType = $isExcel
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf';
        $downloadName = $this->sanitizeFileName($report->name).$extension;

        $headers = [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$downloadName.'"',
        ];

        if (Storage::exists($filePath)) {
            $content = Storage::get($filePath);

            return response($content, 200, $headers);
        }

        $publicPath = public_path($filePath);
        if (file_exists($publicPath)) {
            $content = file_get_contents($publicPath);

            return response($content, 200, $headers);
        }

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            return response($content, 200, $headers);
        }

        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado en el servidor.',
        ], 404);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function buildExcelSpreadsheet(array $validated): Spreadsheet
    {
        $tipo = (string) $validated['tipoReporte'];
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ce4634']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        match ($tipo) {
            'inventario' => $this->excelInventario($sheet, $headerStyle, (int) $validated['grupo_id'], (int) $validated['inventario_id']),
            'grupo' => $this->excelGrupo($sheet, $headerStyle, (int) $validated['grupo_id']),
            'allInventories' => $this->excelTodosInventarios($sheet, $headerStyle),
            'goods' => $this->excelBienes($sheet, $headerStyle),
            'serial' => $this->excelEquipos($sheet, $headerStyle),
            'removedGoods' => $this->excelDadosDeBaja($sheet, $headerStyle),
            'historial' => $this->excelHistorial($sheet, $headerStyle),
            default => throw ValidationException::withMessages(['tipoReporte' => 'Tipo de reporte no soportado.']),
        };

        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function excelInventario(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs, int $groupId, int $inventoryId): void
    {
        $data = $this->inventoryData($groupId, $inventoryId);
        $sheet->setTitle('Inventario');
        $sheet->fromArray(['Bien', 'Tipo', 'Cantidad'], null, 'A1');
        $sheet->getStyle('A1:C1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['goods'] as $g) {
            $sheet->fromArray([$g->bien, $g->tipo, $g->cantidad], null, "A{$row}");
            $row++;
        }
    }

    private function excelGrupo(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs, int $groupId): void
    {
        $data = $this->groupData($groupId);
        $sheet->setTitle('Grupo');
        $sheet->fromArray(['Inventario', 'Bien', 'Tipo', 'Cantidad'], null, 'A1');
        $sheet->getStyle('A1:D1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['inventories'] as $inv) {
            foreach ($inv->goods as $g) {
                $sheet->fromArray([$inv->nombre, $g->bien, $g->tipo, $g->cantidad], null, "A{$row}");
                $row++;
            }
        }
    }

    private function excelTodosInventarios(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs): void
    {
        $data = $this->allInventoriesData();
        $sheet->setTitle('Todos los inventarios');
        $sheet->fromArray(['Grupo', 'Inventario', 'Bien', 'Tipo', 'Cantidad'], null, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['groups'] as $grp) {
            foreach ($grp->inventories as $inv) {
                foreach ($inv->goods as $g) {
                    $sheet->fromArray([$grp->nombre, $inv->nombre, $g->bien, $g->tipo, $g->cantidad], null, "A{$row}");
                    $row++;
                }
            }
        }
    }

    private function excelBienes(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs): void
    {
        $data = $this->goodsData();
        $sheet->setTitle('Bienes');
        $sheet->fromArray(['Bien', 'Tipo', 'Total Cantidad'], null, 'A1');
        $sheet->getStyle('A1:C1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['goods'] as $g) {
            $sheet->fromArray([$g->bien, $g->tipo_bien, $g->total_cantidad], null, "A{$row}");
            $row++;
        }
    }

    private function excelEquipos(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs): void
    {
        $data = $this->serialGoodsData();
        $sheet->setTitle('Equipos');
        $sheet->fromArray(['Bien', 'Serial', 'Marca', 'Modelo', 'Estado', 'Inventario', 'Condiciones'], null, 'A1');
        $sheet->getStyle('A1:G1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['groupedGoods'] as $group) {
            foreach ($group['items'] as $item) {
                $sheet->fromArray([
                    $item->bien, $item->serial, $item->marca,
                    $item->modelo, $item->estado, $item->nombre_inventario, $item->condiciones_tecnicas,
                ], null, "A{$row}");
                $row++;
            }
        }
    }

    private function excelDadosDeBaja(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs): void
    {
        $data = $this->removedGoodsData();
        $sheet->setTitle('Dados de baja');
        $sheet->fromArray(['Bien', 'Tipo', 'Cantidad/Serial', 'Motivo', 'Grupo', 'Inventario',  'Fecha'], null, 'A1');
        $sheet->getStyle('A1:H1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['removedByQuantity'] as $r) {
            $sheet->fromArray([$r->bien, $r->tipo, $r->cantidad, $r->motivo, $r->grupo, $r->inventario, $r->fecha_baja], null, "A{$row}");
            $row++;
        }
        foreach ($data['removedBySerial'] as $r) {
            $sheet->fromArray([$r->bien, 'Serial', $r->serial, $r->motivo, $r->grupo, $r->inventario, $r->fecha_baja], null, "A{$row}");
            $row++;
        }
    }

    private function excelHistorial(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $hs): void
    {
        $data = $this->historialData();
        $sheet->setTitle('Historial');
        $sheet->fromArray(['Fecha', 'Usuario', 'Acción', 'Modelo', 'Descripción'], null, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray($hs);
        $row = 2;
        foreach ($data['logs'] as $log) {
            $sheet->fromArray([
                optional($log->created_at)->format('d/m/Y H:i'),
                $log->user?->name ?? '—',
                $log->action,
                $log->model,
                $log->description,
            ], null, "A{$row}");
            $row++;
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{view: string, data: array<string, mixed>, paper: string, orientation: string}
     */
    private function buildStyledPayload(array $validated): array
    {
        $branding = tenant()?->branding;
        $timezone = $branding?->timezone_value ?? 'America/Bogota';

        $common = [
            'date' => now()->setTimezone($timezone)->format('d/m/Y'),
            'logoDataUri' => $this->logoDataUri($branding),
            'branding' => $branding,
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
            'historial' => [
                'view' => 'reports.pdf.reporte_de_historial',
                'data' => array_merge($common, $this->historialData()),
                'paper' => 'A4',
                'orientation' => 'landscape',
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

        if (! $inventory || (int) $inventory->group_id !== $groupId) {
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

        if (! $group) {
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

        if (! Schema::hasTable('asset_equipments_removed')) {
            $removedBySerial = collect();
        } else {
            try {
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
            } catch (QueryException $exception) {
                Log::warning('No se pudieron consultar las bajas por serial para reportes.', [
                    'message' => $exception->getMessage(),
                    'connection' => DB::getDefaultConnection(),
                ]);

                $removedBySerial = collect();
            }
        }

        $totalRemoved = $removedByQuantity->count() + $removedBySerial->count();
        $totalRemovedUnits = (int) $removedByQuantity->sum('cantidad') + $removedBySerial->count();

        return compact('removedByQuantity', 'removedBySerial', 'totalRemoved', 'totalRemovedUnits');
    }

    /**
     * @return array{logs: Collection<int, object>, totalRecords: int, weekCount: int, todayCount: int, activeUsersToday: int, filters: array<string, null>}
     */
    private function historialData(): array
    {
        $logs = \App\Models\ActivityLog::with('user')->orderBy('created_at', 'desc')->get();

        return [
            'logs' => $logs,
            'totalRecords' => $logs->count(),
            'weekCount' => \App\Models\ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'todayCount' => \App\Models\ActivityLog::whereDate('created_at', today())->count(),
            'activeUsersToday' => \App\Models\ActivityLog::distinct('user_id')->whereDate('created_at', today())->count('user_id'),
            'filters' => [
                'user' => null,
                'action' => null,
                'model' => null,
                'date_from' => null,
                'date_to' => null,
                'search' => null,
            ],
        ];
    }

    private function logoDataUri(?\App\Models\Central\TenantBranding $branding = null): ?string
    {
        $logoPath = $branding?->logo_report ?? 'assets/images/logoUniguajira.png';
        $path = public_path($logoPath);

        if (! file_exists($path)) {
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

        return 'data:'.$mime.';base64,'.base64_encode($imageData);
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

        return $name !== '' ? $name : 'reporte_'.now()->format('Y-m-d_H-i-s');
    }
}
