<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Helpers\ActivityLogger;

class GroupController extends Controller
{
    /**
     * GET /api/groups/getAll
     * Devuelve grupos para selects de reportes.
     */
    public function getAll()
    {
        $groups = Group::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(static fn (Group $group): array => [
                'id' => $group->id,
                'nombre' => $group->name,
            ]);

        return response()->json($groups);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $groups = Group::withCount('inventories')->get();

        if ($request->ajax()) {
            return view('inventories.groups', compact('groups'))
                ->renderSections()['content'];
        }

        return view('inventories.groups', compact('groups'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $nombre = $request->nombre;

        // Verificar existencia por nombre
        if (Group::where('name', $nombre)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un grupo con ese nombre.'
            ], 409);
        }

        $group = Group::create(['name' => $nombre]);

        // ✅ Registrar actividad
        ActivityLogger::created(Group::class, $group->id, $group->name);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente',
            'id' => $group->id,
            'data' => $group,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'nombre' => 'required|string|max:255',
        ]);

        $id = $request->id;
        $newName = $request->nombre;

        $group = Group::find($id);
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo. El grupo con ID especificado no existe.'
            ], 404);
        }

        // Si el nuevo nombre ya existe en otro grupo
        $exists = Group::where('name', $newName)->where('id', '!=', $id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo. El nombre ya existe.'
            ], 400);
        }

        // ✅ Guardar valores anteriores
        $oldValues = ['name' => $group->name];

        $group->name = $newName;
        $saved = $group->save();

        if (!$saved) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo por un error desconocido.'
            ], 400);
        }

        // ✅ Registrar actividad
        ActivityLogger::updated(
            Group::class,
            $group->id,
            $group->name,
            $oldValues,
            ['name' => $group->name]
        );

        return response()->json([
            'success' => true,
            'message' => 'Grupo actualizado exitosamente.',
            'data' => $group,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (empty($id)) {
            return response()->json(['success' => false, 'message' => 'El ID del grupo es requerido.'], 400);
        }

        $group = Group::find($id);
        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Grupo no encontrado'], 404);
        }

        // Verificar inventarios asociados
        if ($group->inventories()->exists()) {
            return response()->json(['success' => false, 'message' => 'El grupo tiene inventarios asociados.']);
        }

        $groupName = $group->name; // Guardar antes de eliminar

        try {
            $group->delete();

            // ✅ Registrar actividad
            ActivityLogger::deleted(Group::class, $id, $groupName);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al intentar eliminar el grupo.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Grupo eliminado exitosamente.']);
    }
}
