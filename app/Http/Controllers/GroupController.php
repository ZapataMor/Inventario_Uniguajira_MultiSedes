<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\Group;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GroupController extends Controller
{
    /**
     * GET /api/groups/getAll
     * Obtiene todos los grupos con su ID y nombre, ordenados alfabéticamente,
     * para ser utilizados en componentes de interfaz como selectores en reportes.
     * Se transforma la colección para devolver un formato específico esperado por el frontend.
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
     * Muestra un listado de todos los grupos.
     * Se incluye el conteo de inventarios asociados a cada grupo para proporcionar información adicional.
     * Se diferencia entre peticiones AJAX para actualizaciones parciales y peticiones normales para carga completa de página.
     */
    public function index(Request $request)
    {
        $isPortalInventoryCatalog = ! tenant() && $request->user()?->isGlobalAdmin();
        $groupsBySede = collect();

        if ($isPortalInventoryCatalog) {
            $groupsBySede = $this->getGroupsBySedeForPortal();
            $groups = $groupsBySede->flatMap(fn (array $sede) => $sede['groups'])->values();
        } else {
            $groups = Group::withCount('inventories')->orderBy('name')->get();
        }

        if ($request->ajax()) {
            return view('inventories.groups', compact('groups', 'groupsBySede', 'isPortalInventoryCatalog'))
                ->renderSections()['content'];
        }

        return view('inventories.groups', compact('groups', 'groupsBySede', 'isPortalInventoryCatalog'));
    }

    /**
     * Almacena un nuevo grupo en la base de datos.
     * Se valida que el nombre no exista previamente para mantener la unicidad,
     * se crea el registro y se registra la acción en el log de actividad.
     */
    public function store(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $nombre = $request->nombre;

        // Se verifica si ya existe un grupo con el mismo nombre para evitar duplicados.
        if (Group::where('name', $nombre)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un grupo con ese nombre.',
            ], 409);
        }

        $group = Group::create(['name' => $nombre]);

        // Se registra la creación del grupo para mantener una traza de auditoría.
        ActivityLogger::created(Group::class, $group->id, $group->name);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente',
            'id' => $group->id,
            'data' => $group,
        ]);
    }

    /**
     * Actualiza un grupo existente en la base de datos.
     * Se verifica que el grupo exista y que el nuevo nombre no esté siendo usado por otro grupo.
     * Se registran los valores anteriores y nuevos para la auditoría.
     */
    public function update(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $request->validate([
            'id' => 'required|integer',
            'nombre' => 'required|string|max:255',
        ]);

        $id = $request->id;
        $newName = $request->nombre;

        $group = Group::find($id);
        if (! $group) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo. El grupo con ID especificado no existe.',
            ], 404);
        }

        // Se verifica que el nuevo nombre no esté siendo utilizado por otro grupo diferente al actual.
        $exists = Group::where('name', $newName)->where('id', '!=', $id)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo. El nombre ya existe.',
            ], 400);
        }

        // Se captura el valor anterior del nombre para poder registrar el cambio.
        $oldValues = ['name' => $group->name];

        $group->name = $newName;
        $saved = $group->save();

        if (! $saved) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el grupo por un error desconocido.',
            ], 400);
        }

        // Se registra la actualización con los valores antiguos y nuevos para la auditoría.
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
     * Elimina un grupo específico de la base de datos.
     * Se verifica que el grupo exista y que no tenga inventarios asociados antes de proceder,
     * para mantener la integridad referencial. Se registra la acción en el log.
     */
    public function destroy(string $id)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        if (empty($id)) {
            return response()->json(['success' => false, 'message' => 'El ID del grupo es requerido.'], 400);
        }

        $group = Group::find($id);
        if (! $group) {
            return response()->json(['success' => false, 'message' => 'Grupo no encontrado'], 404);
        }

        // Se verifica si el grupo tiene inventarios asociados para prevenir la eliminación si está en uso.
        if ($group->inventories()->exists()) {
            return response()->json(['success' => false, 'message' => 'El grupo tiene inventarios asociados.']);
        }

        $groupName = $group->name; // Se guarda el nombre antes de eliminar para el registro de actividad.

        try {
            $group->delete();

            // Se registra la eliminación del grupo.
            ActivityLogger::deleted(Group::class, $id, $groupName);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al intentar eliminar el grupo.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Grupo eliminado exitosamente.']);
    }

    /**
     * Consulta grupos de cada sede activa para mostrarlos en el portal central.
     */
    private function getGroupsBySedeForPortal(): Collection
    {
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->with(['branding', 'domains'])
            ->orderBy('id')
            ->get();

        $tenantConnections = app(TenantConnectionManager::class);

        return $tenants->map(function (Tenant $tenant) use ($tenantConnections) {
            return $tenantConnections->runForTenant($tenant, function (Tenant $tenant) {
                $groups = Group::withCount('inventories')
                    ->orderBy('name')
                    ->get();

                $sedeName = $this->resolveSedeName($tenant);

                return [
                    'tenant_id' => $tenant->id,
                    'tenant_slug' => $tenant->slug,
                    'sede_name' => $sedeName,
                    'dropdown_label' => "Grupos sede {$sedeName}",
                    'groups' => $groups,
                ];
            });
        });
    }

    private function resolveSedeName(Tenant $tenant): string
    {
        $rawName = trim((string) ($tenant->branding?->sede_name ?: $tenant->name ?: $tenant->slug));
        $normalized = preg_replace('/^sede\s+/iu', '', $rawName);

        return $normalized ?: ucfirst($tenant->slug);
    }
}
