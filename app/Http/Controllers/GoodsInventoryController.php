<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Inventory;
use App\Services\GoodsInventoryService;
use App\Helpers\ActivityLogger;

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
     * Crear bien dentro de un inventario
     * POST /api/goods-inventory/create
     */
    public function store(Request $request)
    {
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
}
