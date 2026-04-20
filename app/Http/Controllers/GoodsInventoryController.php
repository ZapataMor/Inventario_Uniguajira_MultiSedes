<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\Inventory;
use App\Services\GoodsInventoryService;
use App\Helpers\ActivityLogger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

class GoodsInventoryController extends Controller
{
    protected $service;

    public function __construct(GoodsInventoryService $service)
    {
        $this->service = $service;
    }

    // ------------------------------
    // 3. Bienes dentro de un inventario
    // ------------------------------
    public function goodsIndex(Request $request, $groupId, $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        if ($inventory->group_id != $groupId) {
            abort(404);
        }

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
     * Vista dedicada para la carga masiva de bienes a un inventario
     */
    public function excelUploadView(Request $request, $groupId, $inventoryId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        if ((int) $inventory->group_id !== (int) $groupId) {
            abort(404);
        }

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('inventories.goods-inventory-excel-upload', compact('inventory'));
            return $view->renderSections()['content'];
        }

        return view('inventories.goods-inventory-excel-upload', compact('inventory'));
    }


    /**
     * Crear bien dentro de un inventario
     * POST /api/goods-inventory/create
     */
    public function store(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $data = $request->validate([
            'inventarioId' => 'required|integer|exists:inventories,id',
            'bien_id'      => 'required|integer|exists:assets,id',
            'bien_tipo'    => 'required|in:1,2',
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

        // ✅ Registrar actividad
        $asset     = DB::table('assets')->where('id', $data['bien_id'])->first();
        $inventory = Inventory::find($data['inventarioId']);
        ActivityLogger::custom(
            'create',
            "Agregó '{$asset->name}' al inventario: {$inventory->name}",
            [
                'model'      => 'AssetInventory',
                'model_id'   => $id,
                'new_values' => [
                    'asset'     => $asset->name,
                    'inventory' => $inventory->name,
                    'tipo'      => $tipo == 1 ? 'Cantidad' : 'Serial',
                ],
            ]
        );

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
            'serial'               => 'required|string|max:255',
            'descripcion'          => 'nullable|string|max:255',
            'marca'                => 'nullable|string|max:255',
            'modelo'               => 'nullable|string|max:255',
            'estado'               => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:100',
            'condiciones_tecnicas' => 'nullable|string|max:500',
            'condicion_tecnica'    => 'nullable|string|max:500',
            'fecha_ingreso'        => 'nullable|date',
        ]);

        $technicalConditions = $validated['condiciones_tecnicas']
            ?? $validated['condicion_tecnica']
            ?? '';

        $details = [
            'description'          => $validated['descripcion'] ?? '',
            'brand'                => $validated['marca'] ?? '',
            'model'                => $validated['modelo'] ?? '',
            'serial'               => $validated['serial'],
            'state'                => $validated['estado'] ?? 'activo',
            'color'                => $validated['color'] ?? '',
            'technical_conditions' => $technicalConditions,
            'entry_date'           => $validated['fecha_ingreso'] ?? now()->toDateString()
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
        abort_if(! auth()->user()?->isAdministrator(), 403);

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

        // ✅ Guardar cantidad anterior para el log
        $oldQuantity = DB::table('asset_quantities')
            ->where('asset_inventory_id', $bienInventario->id)
            ->value('quantity');

        $updated = DB::table('asset_quantities')
            ->where('asset_inventory_id', $bienInventario->id)
            ->update(['quantity' => $cantidad]);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la cantidad.'
            ], 400);
        }

        // ✅ Registrar actividad
        $asset     = DB::table('assets')->where('id', $assetId)->first();
        $inventory = Inventory::find($inventoryId);
        ActivityLogger::custom(
            'update',
            "Actualizó cantidad de '{$asset->name}' en inventario: {$inventory->name}",
            [
                'model'      => 'AssetInventory',
                'model_id'   => $bienInventario->id,
                'old_values' => ['cantidad' => $oldQuantity],
                'new_values' => ['cantidad' => $cantidad],
            ]
        );

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
        abort_if(! auth()->user()?->isAdministrator(), 403);

        if (!$inventoryId || !$goodId) {
            return response()->json([
                'success' => false,
                'message' => "ID requeridos."
            ], 400);
        }

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

        DB::table('asset_inventory')
            ->where('inventory_id', $inventoryId)
            ->where('asset_id', $goodId)
            ->delete();

        // ✅ Registrar actividad
        $inventory = Inventory::find($inventoryId);
        ActivityLogger::custom(
            'delete',
            "Eliminó '{$rel->name}' del inventario: {$inventory->name}",
            [
                'model'    => 'AssetInventory',
                'model_id' => $rel->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Bien eliminado del inventario exitosamente.'
        ]);
    }


    /**
     * Actualizar bien tipo serial
     * POST /api/goods-inventory/update-serial
     */
    public function updateSerial(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $validated = $request->validate([
            'bienEquipoId'         => 'required|integer|exists:asset_equipments,id',
            'serial'               => 'required|string|max:255',
            'descripcion'          => 'nullable|string|max:255',
            'marca'                => 'nullable|string|max:255',
            'modelo'               => 'nullable|string|max:255',
            'estado'               => 'nullable|string|max:100',
            'color'                => 'nullable|string|max:100',
            'condiciones_tecnicas' => 'nullable|string|max:500',
            'condicion_tecnica'    => 'nullable|string|max:500',
            'fecha_ingreso'        => 'nullable|date',
        ]);

        $technicalConditions = $validated['condiciones_tecnicas']
            ?? $validated['condicion_tecnica']
            ?? '';

        $details = [
            'description'          => $validated['descripcion'] ?? '',
            'brand'                => $validated['marca'] ?? '',
            'model'                => $validated['modelo'] ?? '',
            'serial'               => $validated['serial'],
            'status'               => $validated['estado'] ?? 'active',
            'color'                => $validated['color'] ?? '',
            'technical_conditions' => $technicalConditions,
            'entry_date'           => $validated['fecha_ingreso'] ?? now()->toDateString(),
        ];

        try {
            $ok = $this->service->updateSerial(
                $validated['bienEquipoId'],
                $details
            );

            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo actualizar el bien serial.'
                ], 400);
            }

            // ✅ Registrar actividad
            $equipment = DB::table('asset_equipments as ae')
                ->join('asset_inventory as ai', 'ai.id', '=', 'ae.asset_inventory_id')
                ->join('assets as a', 'a.id', '=', 'ai.asset_id')
                ->where('ae.id', $validated['bienEquipoId'])
                ->select('a.name as asset_name', 'ae.serial')
                ->first();

            ActivityLogger::custom(
                'update',
                "Actualizó serial '{$validated['serial']}' de '{$equipment->asset_name}'",
                [
                    'model'      => 'AssetEquipment',
                    'model_id'   => $validated['bienEquipoId'],
                    'new_values' => $details,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Bien serial actualizado exitosamente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }


    /**
     * Eliminar bien tipo serial
     * DELETE /api/goods-inventory/delete-serial/{equipment}
     */
    public function deleteSerial(int $equipmentId)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        // ✅ Obtener datos antes de eliminar para el log
        $equipment = DB::table('asset_equipments as ae')
            ->join('asset_inventory as ai', 'ai.id', '=', 'ae.asset_inventory_id')
            ->join('assets as a', 'a.id', '=', 'ai.asset_id')
            ->join('inventories as i', 'i.id', '=', 'ai.inventory_id')
            ->where('ae.id', $equipmentId)
            ->select('a.name as asset_name', 'ae.serial', 'i.name as inventory_name')
            ->first();

        $deleted = $this->service->deleteSerialGood($equipmentId);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el bien serial del inventario.'
            ], 400);
        }

        // ✅ Registrar actividad
        if ($equipment) {
            ActivityLogger::custom(
                'delete',
                "Eliminó serial '{$equipment->serial}' de '{$equipment->asset_name}' en inventario: {$equipment->inventory_name}",
                [
                    'model'    => 'AssetEquipment',
                    'model_id' => $equipmentId,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Bien serial eliminado del inventario exitosamente.'
        ]);
    }


    /**
     * Dar de baja un bien (registrar en assets_removed y reducir cantidad)
     * POST /api/goods-inventory/remove-good
     */
    public function removeGood(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $validated = $request->validate([
            'bienId'       => 'required|integer|exists:assets,id',
            'inventarioId' => 'required|integer|exists:inventories,id',
            'cantidad'     => 'required|integer|min:1',
            'motivo'       => 'nullable|string|max:500',
        ]);

        $assetId     = $validated['bienId'];
        $inventoryId = $validated['inventarioId'];
        $cantidad    = $validated['cantidad'];
        $motivo      = $validated['motivo'] ?? 'Sin motivo especificado';

        try {
            DB::beginTransaction();

            $asset = DB::table('assets')->where('id', $assetId)->first();

            if (!$asset) {
                return response()->json(['success' => false, 'message' => 'El bien no existe.'], 404);
            }

            if ($asset->type !== 'Cantidad') {
                return response()->json(['success' => false, 'message' => 'Solo se pueden dar de baja bienes de tipo Cantidad.'], 400);
            }

            $assetInventory = DB::table('asset_inventory')
                ->where('asset_id', $assetId)
                ->where('inventory_id', $inventoryId)
                ->first();

            if (!$assetInventory) {
                return response()->json(['success' => false, 'message' => 'El bien no existe en este inventario.'], 404);
            }

            $currentQuantity = DB::table('asset_quantities')
                ->where('asset_inventory_id', $assetInventory->id)
                ->value('quantity');

            if ($currentQuantity === null) {
                return response()->json(['success' => false, 'message' => 'No se encontró el registro de cantidad.'], 404);
            }

            if ($cantidad > $currentQuantity) {
                return response()->json(['success' => false, 'message' => "No hay suficiente cantidad. Disponible: {$currentQuantity}, Solicitado: {$cantidad}"], 400);
            }

            DB::table('assets_removed')->insert([
                'name'         => $asset->name,
                'type'         => $asset->type,
                'image'        => $asset->image,
                'quantity'     => $cantidad,
                'reason'       => $motivo,
                'asset_id'     => $assetId,
                'inventory_id' => $inventoryId,
                'user_id'      => auth()->id(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $newQuantity = $currentQuantity - $cantidad;

            if ($newQuantity > 0) {
                DB::table('asset_quantities')
                    ->where('asset_inventory_id', $assetInventory->id)
                    ->update(['quantity' => $newQuantity]);
            } else {
                DB::table('asset_quantities')
                    ->where('asset_inventory_id', $assetInventory->id)
                    ->delete();

                DB::table('asset_inventory')
                    ->where('id', $assetInventory->id)
                    ->delete();
            }

            DB::commit();

            // ✅ Registrar actividad (después del commit para garantizar consistencia)
            $inventory = Inventory::find($inventoryId);
            ActivityLogger::custom(
                'remove',
                "Dio de baja {$cantidad} unidad(es) de '{$asset->name}' en inventario: {$inventory->name}",
                [
                    'model'      => 'AssetRemoved',
                    'new_values' => [
                        'asset'     => $asset->name,
                        'inventory' => $inventory->name,
                        'cantidad'  => $cantidad,
                        'motivo'    => $motivo,
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Se dieron de baja {$cantidad} unidad(es) de {$asset->name} exitosamente."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el bien: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Dar de baja un bien por serial (1 unidad exacta)
     * POST /api/goods-inventory/remove-good-serial
     */
    public function removeGoodSerial(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $validated = $request->validate([
            'equipmentId'  => 'required|integer|exists:asset_equipments,id',
            'inventarioId' => 'required|integer|exists:inventories,id',
            'motivo'       => 'nullable|string|max:500',
        ]);

        $equipmentId = $validated['equipmentId'];
        $inventoryId = $validated['inventarioId'];
        $motivo      = $validated['motivo'] ?? 'Sin motivo especificado';

        try {
            DB::beginTransaction();

            $equipment = DB::table('asset_equipments as ae')
                ->join('asset_inventory as ai', 'ai.id', '=', 'ae.asset_inventory_id')
                ->join('assets as a', 'a.id', '=', 'ai.asset_id')
                ->where('ae.id', $equipmentId)
                ->select(
                    'ae.*',
                    'ai.inventory_id as inventory_id',
                    'ai.asset_id as asset_id',
                    'a.name as asset_name',
                    'a.type as asset_type',
                    'a.image as asset_image'
                )
                ->first();

            if (!$equipment) {
                return response()->json(['success' => false, 'message' => 'El bien serial no existe.'], 404);
            }

            if ($equipment->asset_type !== 'Serial') {
                return response()->json(['success' => false, 'message' => 'Solo se pueden dar de baja bienes de tipo Serial.'], 400);
            }

            if ((int)$equipment->inventory_id !== (int)$inventoryId) {
                return response()->json(['success' => false, 'message' => 'Este bien serial no pertenece al inventario seleccionado.'], 400);
            }

            DB::table('asset_equipments_removed')->insert([
                'name'                 => $equipment->asset_name,
                'image'                => $equipment->asset_image,
                'description'          => $equipment->description ?? null,
                'brand'                => $equipment->brand ?? null,
                'model'                => $equipment->model ?? null,
                'serial'               => $equipment->serial ?? null,
                'status'               => $equipment->status ?? null,
                'color'                => $equipment->color ?? null,
                'technical_conditions' => $equipment->technical_conditions ?? null,
                'entry_date'           => $equipment->entry_date ?? null,
                'exit_date'            => now(),
                'reason'               => $motivo,
                'asset_id'             => $equipment->asset_id,
                'inventory_id'         => $inventoryId,
                'equipment_id'         => $equipmentId,
                'user_id'              => auth()->user()->id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            DB::table('asset_equipments')
                ->where('id', $equipmentId)
                ->delete();

            DB::commit();

            // ✅ Registrar actividad (después del commit para garantizar consistencia)
            $inventory = Inventory::find($inventoryId);
            ActivityLogger::custom(
                'remove',
                "Dio de baja serial '{$equipment->serial}' de '{$equipment->asset_name}' en inventario: {$inventory->name}",
                [
                    'model'      => 'AssetEquipment',
                    'model_id'   => $equipmentId,
                    'new_values' => [
                        'asset'     => $equipment->asset_name,
                        'serial'    => $equipment->serial,
                        'inventory' => $inventory->name,
                        'motivo'    => $motivo,
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Se dio de baja el bien serial {$equipment->serial} exitosamente."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al dar de baja el bien serial: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Carga masiva de bienes a un inventario desde Excel
     * POST /api/goods-inventory/batchCreate/{inventoryId}
     *
     * Columnas esperadas: Bien*, Tipo*, Serial, Cantidad, Marca, Modelo,
     *                     Descripcion, Estado, Color, Condiciones, Fecha Ingreso
     */
    public function batchCreateFromExcel(Request $request, int $inventoryId)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $inventory = Inventory::findOrFail($inventoryId);

        $rows = $request->input('rows', []);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos válidos.'
            ], 400);
        }

        $created = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $nombre   = trim($row['bien']        ?? '');
                $tipo     = trim($row['tipo']        ?? 'Serial');
                $serial   = trim($row['serial']      ?? '');
                $cantidad = intval($row['cantidad']  ?? 1);
                $marca    = trim($row['marca']       ?? '');
                $modelo   = trim($row['modelo']      ?? '');
                $desc     = trim($row['descripcion'] ?? '');
                $estado   = trim($row['estado']      ?? 'activo');
                $color    = trim($row['color']       ?? '');
                $condicion = trim($row['condiciones'] ?? '');
                $fecha    = trim($row['fecha_ingreso'] ?? '');

                if ($nombre === '') {
                    $errors[] = "Fila {$i}: nombre vacío.";
                    continue;
                }

                // Normalizar tipo
                $tipoNorm = strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';

                // Buscar o crear el bien en el catálogo global
                $asset = Asset::firstOrCreate(
                    ['name' => $nombre],
                    ['type' => $tipoNorm]
                );

                if ($tipoNorm === 'Serial') {
                    if ($serial === '') {
                        $errors[] = "Fila {$i}: serial vacío para '{$nombre}'.";
                        continue;
                    }

                    $validEstados = ['activo', 'inactivo', 'en_mantenimiento'];
                    $estadoFinal  = in_array($estado, $validEstados) ? $estado : 'activo';

                    $result = $this->service->addSerial($inventoryId, $asset->id, [
                        'serial'               => $serial,
                        'brand'                => $marca,
                        'model'                => $modelo,
                        'description'          => $desc,
                        'state'                => $estadoFinal,
                        'color'                => $color,
                        'technical_conditions' => $condicion,
                        'entry_date'           => $fecha ?: now()->toDateString(),
                    ]);

                    if (!$result) {
                        $errors[] = "Fila {$i}: serial '{$serial}' ya existe.";
                    } else {
                        $created++;
                    }
                } else {
                    if ($cantidad < 1) $cantidad = 1;
                    $this->service->addQuantity($inventoryId, $asset->id, $cantidad);
                    $created++;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }

        if ($created > 0) {
            ActivityLogger::custom(
                'batch_create',
                "Cargó {$created} bien(es) al inventario: {$inventory->name}",
                ['model' => 'AssetInventory', 'count' => $created]
            );
        }

        $message = "Se cargaron {$created} bien(es) exitosamente.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' error(es).';
        }

        return response()->json([
            'success' => $created > 0,
            'created' => $created,
            'errors'  => $errors,
            'message' => $message,
        ]);
    }

    /**
     * VersiÃ³n optimizada de la carga masiva al inventario.
     * Reduce consultas repetidas por fila agrupando bienes, relaciones, seriales y cantidades.
     */
    public function batchCreateFromExcelOptimized(Request $request, int $inventoryId)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $inventory = Inventory::findOrFail($inventoryId);

        $rows = $request->input('rows', []);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos vÃ¡lidos.'
            ], 400);
        }

        $created = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            $assetDefinitions = [];
            $serials = [];

            foreach ($rows as $row) {
                $nombre = trim($row['bien'] ?? '');
                if ($nombre === '') {
                    continue;
                }

                $tipo = trim($row['tipo'] ?? 'Serial');
                $assetDefinitions[$nombre] ??= strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';

                $serial = trim($row['serial'] ?? '');
                if ($serial !== '') {
                    $serials[] = $serial;
                }
            }

            $assetsByName = $this->service->getOrCreateAssetsByName($assetDefinitions);

            $pairs = [];
            foreach ($assetsByName as $asset) {
                $pairs[] = [
                    'inventory_id' => $inventoryId,
                    'asset_id' => $asset->id,
                ];
            }

            $relations = $this->service->ensureAssetInventoryRelations($pairs);
            $existingSerials = $this->service->getExistingSerialLookup($serials);
            $newSerials = [];
            $quantityIncrements = [];
            $equipmentsToInsert = [];
            $now = now();

            foreach ($rows as $i => $row) {
                $nombre    = trim($row['bien'] ?? '');
                $tipo      = trim($row['tipo'] ?? 'Serial');
                $serial    = trim($row['serial'] ?? '');
                $cantidad  = intval($row['cantidad'] ?? 1);
                $marca     = trim($row['marca'] ?? '');
                $modelo    = trim($row['modelo'] ?? '');
                $desc      = trim($row['descripcion'] ?? '');
                $estado    = trim($row['estado'] ?? 'activo');
                $color     = trim($row['color'] ?? '');
                $condicion = trim($row['condiciones'] ?? '');
                $fecha     = trim($row['fecha_ingreso'] ?? '');

                if ($nombre === '') {
                    $errors[] = "Fila {$i}: nombre vacÃ­o.";
                    continue;
                }

                $tipoNorm = strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';
                $asset = $assetsByName[$nombre] ?? null;

                if (!$asset) {
                    $errors[] = "Fila {$i}: no se pudo resolver el bien '{$nombre}'.";
                    continue;
                }

                $relationId = $relations[$inventoryId . ':' . $asset->id] ?? null;
                if (!$relationId) {
                    $errors[] = "Fila {$i}: no se pudo preparar la relaciÃ³n inventario-bien para '{$nombre}'.";
                    continue;
                }

                if ($tipoNorm === 'Serial') {
                    if ($serial === '') {
                        $errors[] = "Fila {$i}: serial vacÃ­o para '{$nombre}'.";
                        continue;
                    }

                    $validEstados = ['activo', 'inactivo', 'en_mantenimiento'];
                    $estadoFinal  = in_array($estado, $validEstados) ? $estado : 'activo';
                    $serialKey = $this->service->serialKey($serial);

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
                    $created++;
                    continue;
                }

                if ($cantidad < 1) {
                    $cantidad = 1;
                }

                $quantityIncrements[$relationId] = ($quantityIncrements[$relationId] ?? 0) + $cantidad;
                $created++;
            }

            $this->service->applyQuantityIncrements($quantityIncrements);
            $this->service->insertSerialEquipments($equipmentsToInsert);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }

        if ($created > 0) {
            ActivityLogger::custom(
                'batch_create',
                "CargÃ³ {$created} bien(es) al inventario: {$inventory->name}",
                ['model' => 'AssetInventory', 'count' => $created]
            );
        }

        $message = "Se cargaron {$created} bien(es) exitosamente.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' error(es).';
        }

        return response()->json([
            'success' => $created > 0,
            'created' => $created,
            'errors'  => $errors,
            'message' => $message,
        ]);
    }


    /**
     * Descarga la plantilla Excel para carga masiva en inventario
     * GET /api/goods-inventory/download-template
     */
    public function downloadInventoryTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla');

        // ── Encabezados ──────────────────────────────────────────────
        $headers = [
            'A1' => 'Bien*',
            'B1' => 'Tipo*',
            'C1' => 'Serial',
            'D1' => 'Cantidad',
            'E1' => 'Marca',
            'F1' => 'Modelo',
            'G1' => 'Descripcion',
            'H1' => 'Estado',
            'I1' => 'Color',
            'J1' => 'Condiciones',
            'K1' => 'Fecha Ingreso',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        // ── Estilo encabezados ────────────────────────────────────────
        $headerRange = 'A1:K1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Anchos de columna ─────────────────────────────────────────
        $widths = ['A'=>30,'B'=>12,'C'=>20,'D'=>12,'E'=>18,'F'=>18,'G'=>30,'H'=>20,'I'=>14,'J'=>30,'K'=>16];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Filas de ejemplo ──────────────────────────────────────────
        $examples = [
            ['AIRE ACONDICIONADO MINI SPLIT', 'Serial',   'ABC-001', '',  'Samsung',      'AS24UBAN', '',  'activo', 'Blanco', 'Buen estado', date('Y-m-d')],
            ['SILLA ERGONOMICA',              'Cantidad',  '',        '5', 'Rimax',        '',         '',  '',       '',       '',            ''],
        ];

        foreach ($examples as $rowIdx => $example) {
            $sheet->fromArray($example, null, 'A' . ($rowIdx + 2));
        }

        // ── Nota informativa ─────────────────────────────────────────
        $sheet->setCellValue('A5', '* Campos obligatorios. Tipo: Serial o Cantidad. Estado: activo, inactivo, en_mantenimiento.');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
        ]);
        $sheet->mergeCells('A5:K5');

        // ── Generar y descargar ───────────────────────────────────────
        $filename = 'Plantilla_Carga_Inventario.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control'       => 'max-age=0',
            ]
        );
    }


    /**
     * Vista para carga masiva por localización (desde la página de grupos)
     * GET /groups/localizacion-excel-upload
     */
    public function localizacionExcelUploadView(Request $request)
    {
        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('inventories.groups-localizacion-excel-upload');
            return $view->renderSections()['content'];
        }

        return view('inventories.groups-localizacion-excel-upload');
    }


    /**
     * Carga masiva de bienes distribuyendo por la columna Localización (nombre de inventario)
     * POST /api/goods-inventory/batchCreateByLocalizacion
     */
    public function batchCreateByLocalizacion(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $rows = $request->input('rows', []);

        if (empty($rows)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron datos válidos.'
            ], 400);
        }

        // Recopilar nombres únicos de localización
        $localizacionNames = array_values(array_unique(array_filter(array_map(
            fn($row) => strtolower(trim($row['localizacion'] ?? '')),
            $rows
        ))));

        if (empty($localizacionNames)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la columna Localización en los datos.'
            ], 400);
        }

        // Buscar inventarios por nombre (case-insensitive)
        $inventoriesByName = Inventory::whereRaw('LOWER(name) IN (' . implode(',', array_fill(0, count($localizacionNames), '?')) . ')', $localizacionNames)
            ->get()
            ->keyBy(fn($inv) => strtolower($inv->name));

        $created = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            // Agrupar filas por inventario resuelto
            $rowsByInventory = [];
            foreach ($rows as $i => $row) {
                $localizacion = strtolower(trim($row['localizacion'] ?? ''));
                $nombre       = trim($row['bien'] ?? '');

                if ($localizacion === '') {
                    $errors[] = 'Fila ' . ($i + 2) . ": localización vacía para '{$nombre}'.";
                    continue;
                }

                $inventory = $inventoriesByName[$localizacion] ?? null;
                if (!$inventory) {
                    $errors[] = 'Fila ' . ($i + 2) . ": inventario '{$row['localizacion']}' no encontrado.";
                    continue;
                }

                $rowsByInventory[$inventory->id][] = ['row' => $row, 'index' => $i + 2];
            }

            foreach ($rowsByInventory as $inventoryId => $rowItems) {
                $inventory = Inventory::find($inventoryId);

                $assetDefinitions = [];
                $serials          = [];

                foreach ($rowItems as $item) {
                    $r      = $item['row'];
                    $nombre = trim($r['bien'] ?? '');
                    if ($nombre === '' || str_starts_with($nombre, '*')) continue;

                    $tipo = strtolower(trim($r['tipo'] ?? 'Serial')) === 'cantidad' ? 'Cantidad' : 'Serial';
                    $assetDefinitions[$nombre] ??= $tipo;

                    $serial = $tipo === 'Serial' ? trim($r['serial'] ?? '') : '';
                    if ($serial !== '') {
                        $serials[] = $serial;
                    }
                }

                $assetsByName = $this->service->getOrCreateAssetsByName($assetDefinitions);

                $pairs = [];
                foreach ($assetsByName as $asset) {
                    $pairs[] = ['inventory_id' => $inventoryId, 'asset_id' => $asset->id];
                }

                $relations       = $this->service->ensureAssetInventoryRelations($pairs);
                $existingSerials = $this->service->getExistingSerialLookup($serials);
                $newSerials          = [];
                $quantityIncrements  = [];
                $equipmentsToInsert  = [];
                $now                 = now();

                foreach ($rowItems as $item) {
                    $r         = $item['row'];
                    $rowNum    = $item['index'];
                    $nombre    = trim($r['bien'] ?? '');
                    $tipo      = trim($r['tipo'] ?? 'Serial');
                    $cantidad  = intval($r['cantidad'] ?? 1);
                    $desc      = trim($r['descripcion'] ?? '');
                    $color     = trim($r['color'] ?? '');
                    $condicion = trim($r['condiciones'] ?? '');
                    $fecha     = trim($r['fecha_ingreso'] ?? '');

                    // Ignorar filas vacías o notas de plantilla (inician con *)
                    if ($nombre === '' || str_starts_with($nombre, '*')) {
                        continue;
                    }

                    $tipoNorm = strtolower($tipo) === 'cantidad' ? 'Cantidad' : 'Serial';
                    $serial   = $tipoNorm === 'Serial' ? trim($r['serial'] ?? '') : '';
                    $marca    = $tipoNorm === 'Serial' ? trim($r['marca'] ?? '') : '';
                    $modelo   = $tipoNorm === 'Serial' ? trim($r['modelo'] ?? '') : '';
                    $estado   = $tipoNorm === 'Serial' ? trim($r['estado'] ?? 'activo') : '';
                    $asset    = $assetsByName[$nombre] ?? null;

                    if (!$asset) {
                        $errors[] = "Fila {$rowNum}: no se pudo resolver el bien '{$nombre}'.";
                        continue;
                    }

                    $relationId = $relations[$inventoryId . ':' . $asset->id] ?? null;
                    if (!$relationId) {
                        $errors[] = "Fila {$rowNum}: no se pudo preparar la relación para '{$nombre}'.";
                        continue;
                    }

                    if ($tipoNorm === 'Serial') {
                        if ($serial === '') {
                            $errors[] = "Fila {$rowNum}: serial vacío para '{$nombre}'.";
                            continue;
                        }

                        $validEstados = ['activo', 'inactivo', 'en_mantenimiento'];
                        $estadoFinal  = in_array($estado, $validEstados) ? $estado : 'activo';
                        $serialKey    = $this->service->serialKey($serial);

                        if (isset($existingSerials[$serialKey]) || isset($newSerials[$serialKey])) {
                            $errors[] = "Fila {$rowNum}: serial '{$serial}' ya existe.";
                            continue;
                        }

                        $newSerials[$serialKey]  = true;
                        $equipmentsToInsert[] = [
                            'asset_inventory_id'   => $relationId,
                            'description'          => $desc ?: null,
                            'brand'                => $marca ?: null,
                            'model'                => $modelo ?: null,
                            'serial'               => $serial,
                            'status'               => $estadoFinal,
                            'color'                => $color ?: null,
                            'technical_conditions' => $condicion ?: null,
                            'entry_date'           => $fecha ?: now()->toDateString(),
                            'created_at'           => $now,
                            'updated_at'           => $now,
                        ];
                        $created++;
                        continue;
                    }

                    if ($cantidad < 1) $cantidad = 1;
                    $quantityIncrements[$relationId] = ($quantityIncrements[$relationId] ?? 0) + $cantidad;
                    $created++;
                }

                $this->service->applyQuantityIncrements($quantityIncrements);
                $this->service->insertSerialEquipments($equipmentsToInsert);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }

        if ($created > 0) {
            ActivityLogger::custom(
                'batch_create',
                "Cargó {$created} bien(es) por localización",
                ['model' => 'AssetInventory', 'count' => $created]
            );
        }

        $message = "Se cargaron {$created} bien(es) exitosamente.";
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' error(es).';
        }

        return response()->json([
            'success' => $created > 0,
            'created' => $created,
            'errors'  => $errors,
            'message' => $message,
        ]);
    }


    /**
     * Descarga la plantilla Excel con columna Localización
     * GET /api/goods-inventory/download-localizacion-template
     */
    public function downloadLocalizacionTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plantilla');

        $headers = [
            'A1' => 'Bien*',
            'B1' => 'Tipo*',
            'C1' => 'Serial',
            'D1' => 'Cantidad',
            'E1' => 'Marca',
            'F1' => 'Modelo',
            'G1' => 'Descripcion',
            'H1' => 'Estado',
            'I1' => 'Color',
            'J1' => 'Condiciones',
            'K1' => 'Fecha Ingreso',
            'L1' => 'Localizacion',
        ];

        foreach ($headers as $cell => $label) {
            $sheet->setCellValue($cell, $label);
        }

        $headerRange = 'A1:L1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $widths = ['A'=>30,'B'=>12,'C'=>20,'D'=>12,'E'=>18,'F'=>18,'G'=>30,'H'=>20,'I'=>14,'J'=>30,'K'=>16,'L'=>20];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $examples = [
            ['AIRE ACONDICIONADO MINI SPLIT', 'Serial',   'ABC-001', '',  'Samsung', 'AS24UBAN', '', 'activo', 'Blanco', 'Buen estado', date('Y-m-d'), 'Inventario 1'],
            ['SILLA ERGONOMICA',              'Cantidad',  '',        '5', 'Rimax',   '',         '', '',       '',       '',            '',            'Inventario 2'],
        ];

        foreach ($examples as $rowIdx => $example) {
            $sheet->fromArray($example, null, 'A' . ($rowIdx + 2));
        }

        $sheet->setCellValue('A5', '* Campos obligatorios. Localizacion: nombre exacto del inventario destino.');
        $sheet->getStyle('A5')->applyFromArray([
            'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
        ]);
        $sheet->mergeCells('A5:L5');

        $filename = 'Plantilla_Carga_Localizacion.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control'       => 'max-age=0',
            ]
        );
    }
}
