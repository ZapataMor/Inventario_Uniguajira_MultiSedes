<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetInventory;
use App\Models\Inventory;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ActivityLogger;
use App\Services\GoodsInventoryService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GoodsController extends Controller
{
    protected $inventoryService;

    public function __construct(GoodsInventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Muestra un listado del recurso.
     * Se obtienen los datos desde una vista de base de datos para mejorar la eficiencia de las consultas.
     * Se diferencia entre una petición AJAX y una carga de página normal para poder renderizar
     * secciones específicas (SPA) o la vista completa.
     */
    public function index(Request $request)
    {
        // Se consulta la vista SQL que resume la información de los bienes para evitar lógica compleja en el controlador.
        $dataGoods = DB::table('assets_summary_view')->get();

        if ($request->ajax()) {
            // Si la petición es AJAX, se renderiza únicamente la sección 'content' de la vista para una actualización parcial de la página.
            /** @var \Illuminate\View\View $view */
            $view = view('goods.index', compact('dataGoods'));
            return $view->renderSections()['content'];
        }

        // Si es una petición HTTP estándar, se devuelve la vista completa con el layout.
        return view('goods.index', compact('dataGoods'));
    }

    /**
     * GET /api/goods/get/json
     * Proporciona un listado JSON de todos los bienes con su ID, nombre y tipo para ser consumido por el frontend
     * en componentes dinámicos como selectores o tablas.
     */
    public function getJson()
    {
        // Se obtienen únicamente las columnas necesarias de la tabla de bienes para minimizar la transferencia de datos.
        $goods = Asset::select('id', 'name as bien', 'type as tipo')->get();

        return response()->json($goods);
    }

    /**
     * Almacena un recurso recién creado en la base de datos.
     * Esta acción está restringida únicamente a usuarios con rol de administrador.
     * Se valida la entrada, se procesa una imagen opcional y se registra la acción en el log de actividad.
     */
    public function store(Request $request)
    {
        // Se verifica que el usuario autenticado tenga permisos de administrador; de lo contrario, se aborta la petición.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $request->validate([
            'nombre' => 'required|string|max:255|unique:assets,name',
            'tipo'   => 'required|integer|in:1,2',
            'imagen' => 'nullable|image|max:2048' // 2 MB
        ]);

        // Se guarda la imagen en el disco 'public' si se ha proporcionado una en la petición.
        $path = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('assets/goods', 'public');
        }

        $asset = Asset::create([
            'name'  => $request->nombre,
            'type'  => $request->tipo == 1 ? 'Cantidad' : 'Serial',
            'image' => $path
        ]);

        // Se registra la creación del bien para mantener una traza de auditoría.
        ActivityLogger::created(Asset::class, $asset->id, $asset->name);

        return response()->json([
            'success' => true,
            'message' => 'Bien creado exitosamente.',
            'assetId' => $asset->id
        ]);
    }

    /**
     * Actualiza un recurso específico en la base de datos.
     * Requiere permisos de administrador. Se valida la existencia del recurso, los datos de entrada,
     * se gestiona la sustitución de imágenes y se registra el cambio.
     */
    public function update(Request $request)
    {
        // Se asegura de que el usuario sea administrador antes de proceder con la actualización.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::findOrFail($request->id);

        $request->validate([
            'id'     => 'required|integer|exists:assets,id',
            'nombre' => 'required|string|max:255|unique:assets,name,' . $asset->id,
            'imagen' => 'nullable|image|max:2048'
        ]);

        // Se capturan los valores actuales antes de la modificación para poder registrar qué cambió exactamente.
        $oldValues = [
            'name' => $asset->name,
            'image' => $asset->image,
        ];

        // Si se ha subido una nueva imagen, se reemplaza la anterior.
        if ($request->hasFile('imagen')) {
            // Se elimina el archivo de imagen antiguo del sistema de archivos si existe para liberar espacio.
            if ($asset->image && Storage::disk('public')->exists($asset->image)) {
                Storage::disk('public')->delete($asset->image);
            }

            // Se guarda la nueva imagen en el disco.
            $asset->image = $request->file('imagen')->store('assets/goods', 'public');
        }

        // Se actualiza el nombre del bien.
        $asset->name = $request->nombre;

        $asset->save();

        // Se registra la actividad de actualización, incluyendo los valores antiguos y nuevos para una auditoría detallada.
        ActivityLogger::updated(
            Asset::class,
            $asset->id,
            $asset->name,
            $oldValues,
            [
                'name' => $asset->name,
                'image' => $asset->image,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bien actualizado correctamente.'
        ]);
    }

    /**
     * Elimina un recurso específico de la base de datos.
     * Función exclusiva para administradores. Antes de eliminar, se verifica que el bien no tenga existencias
     * (cantidades o equipos) asociadas para mantener la integridad de los datos. También se elimina su imagen asociada.
     */
    public function destroy(string $id)
    {
        // Se valida que el usuario tenga el rol adecuado para realizar esta operación.
        abort_if(auth()->user()->role !== 'administrador', 403);

        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => 'Bien no encontrado.'
            ], 404);
        }

        // Se calcula la cantidad total de este bien en el inventario (suma de cantidades y equipos) para prevenir la eliminación si está en uso.
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

        $assetName = $asset->name; // Se guarda el nombre para el log de actividad, ya que el objeto se eliminará.

        // Se elimina la imagen del bien del disco duro para no dejar archivos huérfanos.
        if ($asset->image && Storage::disk('public')->exists($asset->image)) {
            Storage::disk('public')->delete($asset->image);
        }

        $asset->delete();

        // Se registra la eliminación en el log de actividad.
        ActivityLogger::deleted(Asset::class, $id, $assetName);

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado correctamente.'
        ]);
    }

    /**
     * Crea múltiples bienes a partir de una carga por archivo Excel (POST /api/goods/batchCreate).
     * Recibe un array de bienes con sus datos e imágenes opcionales.
     * Su propósito es permitir la creación masiva. Se procesa cada ítem individualmente,
     * manejando errores por fila y registrando al final un resumen de la operación.
     */
    public function batchCreate(Request $request)
    {
        // Solo los administradores pueden realizar cargas masivas.
        abort_if(auth()->user()->role !== 'administrador', 403);

        try {
            $goods = $request->input('goods', $request->input('rows', []));

            if (empty($goods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se recibieron datos válidos.'
                ], 400);
            }

            $created = 0;
            $errors = [];
            $createdAssets = []; // Se almacenan los nombres de los bienes creados para el resumen del log.

            foreach ($goods as $index => $good) {
                $rowNumber = $index + 2;

                try {
                    $normalizedGood = $this->normalizeBatchGood($good);
                    // Se valida que los datos de cada bien sean correctos antes de intentar crearlos.
                    $validator = validator([
                        'nombre' => $normalizedGood['nombre'],
                        'tipo' => $normalizedGood['tipo'],
                    ], [
                        'nombre' => 'required|string|max:255|unique:assets,name',
                        'tipo' => 'required|in:Cantidad,Serial'
                    ], [
                        'tipo.required' => 'El campo tipo es obligatorio.',
                        'tipo.in' => 'El tipo debe ser Cantidad o Serial.',
                    ]);

                    if ($validator->fails()) {
                        $errors[] = "Fila {$rowNumber}: " . $validator->errors()->first();
                        continue;
                    }

                    // Se procesa la imagen si existe para el índice actual.
                    $imagePath = null;
                    $imageKey = "goods_{$index}_imagen";

                    if ($request->hasFile($imageKey)) {
                        $image = $request->file($imageKey);

                        $validator = validator(['imagen' => $image], [
                            'imagen' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
                        ]);

                        if ($validator->fails()) {
                            $errors[] = "Fila {$rowNumber}: " . $validator->errors()->first();
                            continue;
                        }

                        $imagePath = $image->store('assets/goods', 'public');
                    }

                    // Se crea el registro del bien en la base de datos.
                    $asset = Asset::create([
                        'name' => $normalizedGood['nombre'],
                        'type' => $normalizedGood['tipo'],
                        'image' => $imagePath
                    ]);

                    $createdAssets[] = $asset->name;
                    $created++;

                } catch (\Exception $e) {
                    // Se captura cualquier excepción inesperada durante la creación de un bien para que el proceso continúe con los demás.
                    $errors[] = "Fila {$rowNumber}: {$e->getMessage()}";
                }
            }

            // Si se creó al menos un bien, se registra una entrada de actividad personalizada para la operación masiva.
            if ($created > 0) {
                ActivityLogger::custom(
                    'batch_create',
                    "Creó {$created} bien(es) mediante carga masiva: " . implode(', ', array_slice($createdAssets, 0, 5)) . ($created > 5 ? '...' : ''),
                    [
                        'model' => 'Asset',
                        'count' => $created,
                        'assets' => $createdAssets,
                    ]
                );
            }

            $message = "Se crearon {$created} bien(es) exitosamente.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(es).";
            }

            return response()->json([
                'success' => $created > 0,
                'message' => $message,
                'created' => $created,
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            // Error general del proceso de carga masiva, como problemas con la petición misma.
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga una plantilla Excel (GET /api/goods/download-template) con el formato correcto
     * para la importación de bienes. Esto facilita al usuario entender la estructura de datos requerida.
     */
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla');

        $headers = [
            'A1' => 'Nombre*',
            'B1' => 'Tipo*',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1B5E20'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(18);

        $examples = [
            ['SILLA ERGONOMICA', 'Cantidad'],
            ['COMPUTADOR PORTATIL', 'Serial'],
        ];

        foreach ($examples as $rowIndex => $example) {
            $sheet->fromArray($example, null, 'A' . ($rowIndex + 2));
        }

        $sheet->setCellValue('A5', '* Campos obligatorios. Tipo: Cantidad o Serial. Mayusculas y minusculas no importan.');
        $sheet->mergeCells('A5:B5');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '555555'],
            ],
        ]);

        $filename = 'Plantilla_Crear_Bienes.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    /**
     * Muestra la vista para la subida de bienes mediante archivo Excel (GET /api/goods/excel-upload/view).
     * Al igual que en el índice, soporta peticiones AJAX para cargar solo el contenido del modal
     * y peticiones normales para cargar una página completa.
     */
    public function excelUploadView(Request $request)
    {
        if ($request->ajax()) {
            // Renderiza solo el contenido interno del modal para una experiencia de aplicación de una sola página (SPA).
            return view('components.modal.goods.excel-upload')
                ->renderSections()['content'];
        }

        return view('goods.excel-upload');
    }

    /**
     * Vista para carga masiva global con asignación a inventario
     * GET /goods/excel-upload-global
     */
    public function excelUploadGlobalView(Request $request)
    {
        return $this->excelUploadView($request);
    }

    /**
     * Carga masiva global de bienes: crea en catálogo y, si hay localización,
     * asigna al inventario cuyo nombre coincida (case-insensitive).
     * POST /api/goods/batchCreateGlobal
     */
    public function batchCreateGlobal(Request $request)
    {
        return $this->batchCreate($request);

        abort_if(auth()->user()->role !== 'administrador', 403);

        $rows = $request->input('rows', []);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos válidos.',
            ], 400);
        }

        $created        = 0;
        $assigned       = 0;
        $errors         = [];
        $createdAssets  = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $nombre       = trim($row['bien']          ?? '');
                $tipo         = trim($row['tipo']          ?? 'Serial');
                $localizacion = trim($row['localizacion']  ?? '');
                $serial       = trim($row['serial']        ?? '');
                $cantidad     = max(1, intval($row['cantidad'] ?? 1));
                $marca        = trim($row['marca']         ?? '');
                $modelo       = trim($row['modelo']        ?? '');
                $desc         = trim($row['descripcion']   ?? '');
                $estado       = trim($row['estado']        ?? 'activo');
                $color        = trim($row['color']         ?? '');
                $condicion    = trim($row['condiciones']   ?? '');
                $fecha        = trim($row['fecha_ingreso'] ?? '');

                if ($nombre === '') {
                    $errors[] = "Fila {$i}: nombre de bien vacío.";
                    continue;
                }

                $tipoNorm = strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';

                // 1. Crear o reutilizar bien en el catálogo global
                $asset = Asset::firstOrCreate(
                    ['name' => $nombre],
                    ['type' => $tipoNorm]
                );

                $createdAssets[] = $asset->name;
                $created++;

                // 2. Si hay localización, buscar inventario (case-insensitive + trim)
                if ($localizacion !== '') {
                    $inventory = Inventory::whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [mb_strtolower(trim($localizacion))]
                    )->first();

                    if (!$inventory) {
                        $errors[] = "Fila {$i}: inventario '{$localizacion}' no encontrado (bien '{$nombre}' creado en catálogo).";
                    } else {
                        $validEstados = ['activo', 'inactivo', 'en_mantenimiento'];
                        $estadoFinal  = in_array($estado, $validEstados) ? $estado : 'activo';

                        if ($tipoNorm === 'Serial') {
                            if ($serial === '') {
                                $errors[] = "Fila {$i}: serial vacío para '{$nombre}' (bien creado en catálogo sin asignar).";
                            } else {
                                $result = $this->inventoryService->addSerial(
                                    $inventory->id,
                                    $asset->id,
                                    [
                                        'serial'               => $serial,
                                        'brand'                => $marca,
                                        'model'                => $modelo,
                                        'description'          => $desc,
                                        'state'                => $estadoFinal,
                                        'color'                => $color,
                                        'technical_conditions' => $condicion,
                                        'entry_date'           => $fecha ?: now()->toDateString(),
                                    ]
                                );

                                if (!$result) {
                                    $errors[] = "Fila {$i}: serial '{$serial}' ya existe.";
                                } else {
                                    $assigned++;
                                }
                            }
                        } else {
                            $this->inventoryService->addQuantity($inventory->id, $asset->id, $cantidad);
                            $assigned++;
                        }
                    }
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }

        if ($created > 0) {
            ActivityLogger::custom(
                'batch_create',
                "Carga global: {$created} bien(es) al catálogo, {$assigned} asignado(s) a inventario.",
                ['model' => 'Asset', 'count' => $created, 'assigned' => $assigned]
            );
        }

        $message = "Se procesaron {$created} bien(es): {$assigned} asignado(s) a inventario.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' advertencia(s).';
        }

        return response()->json([
            'success'  => $created > 0,
            'created'  => $created,
            'assigned' => $assigned,
            'errors'   => $errors,
            'message'  => $message,
        ]);
    }

    /**
     * VersiÃ³n optimizada de la carga masiva global.
     * Reduce consultas por fila precargando bienes, inventarios, relaciones y seriales.
     */
    public function batchCreateGlobalOptimized(Request $request)
    {
        return $this->batchCreate($request);

        abort_if(auth()->user()->role !== 'administrador', 403);

        $rows = $request->input('rows', []);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos vÃ¡lidos.',
            ], 400);
        }

        $created = 0;
        $assigned = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $assetDefinitions = [];
            $requestedLocations = [];
            $serials = [];

            foreach ($rows as $row) {
                $nombre = trim($row['bien'] ?? '');
                if ($nombre === '') {
                    continue;
                }

                $tipo = trim($row['tipo'] ?? 'Serial');
                $assetDefinitions[$nombre] ??= strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';

                $localizacion = trim($row['localizacion'] ?? '');
                if ($localizacion !== '') {
                    $requestedLocations[mb_strtolower($localizacion)] = true;
                }

                $serial = trim($row['serial'] ?? '');
                if ($serial !== '') {
                    $serials[] = $serial;
                }
            }

            $assetsByName = $this->inventoryService->getOrCreateAssetsByName($assetDefinitions);

            $inventoriesByName = Inventory::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($inventory) => [mb_strtolower(trim($inventory->name)) => $inventory])
                ->all();

            $pairs = [];
            foreach ($rows as $row) {
                $nombre = trim($row['bien'] ?? '');
                $localizacion = trim($row['localizacion'] ?? '');

                if ($nombre === '' || $localizacion === '' || !isset($assetsByName[$nombre])) {
                    continue;
                }

                $inventory = $inventoriesByName[mb_strtolower($localizacion)] ?? null;
                if (!$inventory) {
                    continue;
                }

                $pairs[] = [
                    'inventory_id' => $inventory->id,
                    'asset_id' => $assetsByName[$nombre]->id,
                ];
            }

            $relations = $this->inventoryService->ensureAssetInventoryRelations($pairs);
            $existingSerials = $this->inventoryService->getExistingSerialLookup($serials);
            $newSerials = [];
            $quantityIncrements = [];
            $equipmentsToInsert = [];
            $now = now();

            foreach ($rows as $i => $row) {
                $nombre       = trim($row['bien'] ?? '');
                $tipo         = trim($row['tipo'] ?? 'Serial');
                $localizacion = trim($row['localizacion'] ?? '');
                $serial       = trim($row['serial'] ?? '');
                $cantidad     = max(1, intval($row['cantidad'] ?? 1));
                $marca        = trim($row['marca'] ?? '');
                $modelo       = trim($row['modelo'] ?? '');
                $desc         = trim($row['descripcion'] ?? '');
                $estado       = trim($row['estado'] ?? 'activo');
                $color        = trim($row['color'] ?? '');
                $condicion    = trim($row['condiciones'] ?? '');
                $fecha        = trim($row['fecha_ingreso'] ?? '');

                if ($nombre === '') {
                    $errors[] = "Fila {$i}: nombre de bien vacÃ­o.";
                    continue;
                }

                $tipoNorm = strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';
                $asset = $assetsByName[$nombre] ?? null;

                if (!$asset) {
                    $errors[] = "Fila {$i}: no se pudo resolver el bien '{$nombre}'.";
                    continue;
                }

                $created++;

                if ($localizacion === '') {
                    continue;
                }

                $inventory = $inventoriesByName[mb_strtolower($localizacion)] ?? null;
                if (!$inventory) {
                    $errors[] = "Fila {$i}: inventario '{$localizacion}' no encontrado (bien '{$nombre}' creado en catÃ¡logo).";
                    continue;
                }

                $relationId = $relations[$inventory->id . ':' . $asset->id] ?? null;
                if (!$relationId) {
                    $errors[] = "Fila {$i}: no se pudo preparar la relaciÃ³n inventario-bien para '{$nombre}'.";
                    continue;
                }

                $validEstados = ['activo', 'inactivo', 'en_mantenimiento'];
                $estadoFinal  = in_array($estado, $validEstados) ? $estado : 'activo';

                if ($tipoNorm === 'Serial') {
                    if ($serial === '') {
                        $errors[] = "Fila {$i}: serial vacÃ­o para '{$nombre}' (bien creado en catÃ¡logo sin asignar).";
                        continue;
                    }

                    $serialKey = $this->inventoryService->serialKey($serial);
                    if (isset($existingSerials[$serialKey]) || isset($newSerials[$serialKey])) {
                        $errors[] = "Fila {$i}: serial '{$serial}' ya existe.";
                        continue;
                    }

                    $newSerials[$serialKey] = true;
                    $equipmentsToInsert[] = [
                        'asset_inventory_id' => $relationId,
                        'description' => $desc ?: null,
                        'brand' => $marca ?: null,
                        'model' => $modelo ?: null,
                        'serial' => $serial,
                        'status' => $estadoFinal,
                        'color' => $color ?: null,
                        'technical_conditions' => $condicion ?: null,
                        'entry_date' => $fecha ?: now()->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $assigned++;
                    continue;
                }

                $quantityIncrements[$relationId] = ($quantityIncrements[$relationId] ?? 0) + $cantidad;
                $assigned++;
            }

            $this->inventoryService->applyQuantityIncrements($quantityIncrements);
            $this->inventoryService->insertSerialEquipments($equipmentsToInsert);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }

        if ($created > 0) {
            ActivityLogger::custom(
                'batch_create',
                "Carga global: {$created} bien(es) al catÃ¡logo, {$assigned} asignado(s) a inventario.",
                ['model' => 'Asset', 'count' => $created, 'assigned' => $assigned]
            );
        }

        $message = "Se procesaron {$created} bien(es): {$assigned} asignado(s) a inventario.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' advertencia(s).';
        }

        return response()->json([
            'success'  => $created > 0,
            'created'  => $created,
            'assigned' => $assigned,
            'errors'   => $errors,
            'message'  => $message,
        ]);
    }

    private function normalizeBatchGood(array $good): array
    {
        return [
            'nombre' => trim((string) ($good['nombre'] ?? $good['bien'] ?? '')),
            'tipo' => $this->normalizeBulkType($good['tipo'] ?? null),
        ];
    }

    private function normalizeBulkType(mixed $value): ?string
    {
        $type = mb_strtolower(trim((string) $value));

        return match ($type) {
            '1', 'cantidad' => 'Cantidad',
            '2', 'serial' => 'Serial',
            default => null,
        };
    }
}
