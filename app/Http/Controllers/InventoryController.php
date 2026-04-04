<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use App\Helpers\ActivityLogger;

class InventoryController extends Controller
{
    /**
     * GET /api/inventories/getByGroupId/{groupId}
     * Devuelve inventarios para selects de reportes.
     */
    public function getByGroupId(int $groupId)
    {
        $inventories = Inventory::query()
            ->where('group_id', $groupId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(static fn (Inventory $inventory): array => [
                'id' => $inventory->id,
                'nombre' => $inventory->name,
            ]);

        return response()->json($inventories);
    }


    // ------------------------------
    // 2. Inventarios de un grupo
    // ------------------------------
    public function index(Request $request, $groupId)
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
            /** @var \Illuminate\View\View $view */
            $view = view('inventories.inventories', compact('group', 'inventories'));
            return $view->renderSections()['content'];
        }

        return view('inventories.inventories', compact('group', 'inventories'));
    }


    // -----------------------------------
    // 4. Bienes tipo serial (detalles)
    // -----------------------------------
    public function serialsIndex(Request $request, $groupId, $inventoryId, $assetId)
    {
        $inventory = Inventory::findOrFail($inventoryId);

        // Si inventory->group_id no coincide con $groupId, lanzar 404
        if ($inventory->group_id != $groupId) {
            abort(404);
        }

        $serials = DB::table('serial_goods_view')
            ->where('inventory_id', $inventoryId)
            ->where('asset_id', $assetId)
            ->get();

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view( 'inventories.serials-goods-inventory',
                        compact('inventory', 'serials') );
            return $view->renderSections()['content'];
        }

        return view('inventories.serials-goods-inventory',
            compact('inventory', 'serials')
        );
    }


    public function updateEstado(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'id_inventario' => 'required|integer|exists:inventories,id',
            'estado' => 'required|integer|in:1,2,3'
        ]);

        $inventory = Inventory::findOrFail($request->id_inventario);

        // ✅ Guardar valor anterior
        $oldStatus = $inventory->conservation_status;

        $inventory->conservation_status = $request->estado; // bueno=1, regular=2, malo=3
        $inventory->updated_at = now();
        $inventory->save();

        // ✅ Registrar actividad
        $statusNames = [1 => 'Bueno', 2 => 'Regular', 3 => 'Malo'];
        ActivityLogger::updated(
            Inventory::class,
            $inventory->id,
            $inventory->name,
            ['conservation_status' => $statusNames[$oldStatus] ?? 'Desconocido'],
            ['conservation_status' => $statusNames[$request->estado] ?? 'Desconocido']
        );

        return response()->json([
            'success' => true,
            'message' => 'Estado del inventario actualizado exitosamente.'
        ]);
    }


    // ------------------------------
    // Crear Inventario (API)
    // ------------------------------
    public function create(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'grupo_id' => 'required|integer|exists:groups,id',
            'nombre' => 'required|string|max:255',
        ]);

        $groupId = $request->grupo_id;
        $nombre = $request->nombre;

        // Verificar existencia de inventario con mismo nombre en el grupo
        if (Inventory::where('group_id', $groupId)->where('name', $nombre)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un inventario con ese nombre en el grupo.'
            ], 409);
        }

        $inventory = Inventory::create([
            'group_id' => $groupId,
            'name' => $nombre,
        ]);

        // ✅ Registrar actividad
        ActivityLogger::created(Inventory::class, $inventory->id, $inventory->name);

        return response()->json([
            'success' => true,
            'message' => 'Inventario creado exitosamente.',
            'id' => $inventory->id,
            'data' => $inventory,
        ]);
    }


    // ------------------------------
    // Renombrar Inventario (API)
    // ------------------------------
    public function rename(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'inventory_id' => 'required|integer',
            'nombre' => 'required|string|max:255',
        ]);

        $id = $request->inventory_id;
        $newName = $request->nombre;

        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['success' => false, 'message' => 'El inventario no existe.'], 404);
        }

        // Comprobar nombre duplicado dentro del mismo grupo
        $exists = Inventory::where('group_id', $inventory->group_id)
            ->where('name', $newName)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Ya existe un inventario con ese nombre en el grupo.'], 400);
        }

        // ✅ Guardar valores anteriores
        $oldValues = ['name' => $inventory->name];

        $inventory->name = $newName;
        $saved = $inventory->save();

        if (!$saved) {
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar el inventario.'], 400);
        }

        // ✅ Registrar actividad
        ActivityLogger::updated(
            Inventory::class,
            $inventory->id,
            $inventory->name,
            $oldValues,
            ['name' => $inventory->name]
        );

        return response()->json(['success' => true, 'message' => 'Inventario renombrado correctamente.', 'data' => $inventory]);
    }


    // ------------------------------
    // Actualizar responsable (API)
    // ------------------------------
    public function updateResponsable(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'id' => 'required|integer|exists:inventories,id',
            'responsable' => 'nullable|string|max:255',
        ]);

        $inventory = Inventory::findOrFail($request->id);

        // ✅ Guardar valores anteriores
        $oldValues = ['responsible' => $inventory->responsible];

        $inventory->responsible = $request->responsable;
        $inventory->save();

        // ✅ Registrar actividad
        ActivityLogger::updated(
            Inventory::class,
            $inventory->id,
            $inventory->name,
            $oldValues,
            ['responsible' => $inventory->responsible]
        );

        return response()->json(['success' => true, 'message' => 'Responsable actualizado correctamente.', 'data' => $inventory]);
    }


    // ------------------------------
    // Eliminar Inventario (API)
    // ------------------------------
    public function delete($id)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        if (empty($id)) {
            return response()->json(['success' => false, 'message' => 'El ID del inventario es requerido.'], 400);
        }

        $inventory = Inventory::find($id);
        if (!$inventory) {
            return response()->json(['success' => false, 'message' => 'Inventario no encontrado.'], 404);
        }

        // Verificar si tiene bienes asociados
        if ($inventory->assetInventories()->exists()) {
            return response()->json(['success' => false, 'message' => 'El inventario tiene bienes asociados.']);
        }

        $inventoryName = $inventory->name; // Guardar antes de eliminar

        try {
            $inventory->delete();

            // ✅ Registrar actividad
            ActivityLogger::deleted(Inventory::class, $id, $inventoryName);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al eliminar el inventario.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Inventario eliminado exitosamente.']);
    }

}
