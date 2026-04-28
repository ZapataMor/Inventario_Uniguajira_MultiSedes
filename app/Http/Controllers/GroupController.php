<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\Group;
use App\Models\Inventory;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
     * Busca grupos, inventarios o bienes desde la vista principal de grupos.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:groups,inventories,goods',
            'q' => 'nullable|string|max:120',
        ]);

        $term = trim((string) ($validated['q'] ?? ''));
        if ($term === '') {
            return response()->json(['results' => []]);
        }

        $limit = 20;
        $results = (! tenant() && $request->user()?->isGlobalAdmin())
            ? $this->searchInventoryPortalCatalog($validated['type'], $term, $limit)
            : $this->searchInventoryTenantCatalog($validated['type'], $term, $limit);

        return response()->json([
            'results' => $results->values(),
        ]);
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

    private function searchInventoryPortalCatalog(string $type, string $term, int $limit): Collection
    {
        $tenants = Tenant::query()
            ->where('is_active', true)
            ->with('branding')
            ->orderBy('id')
            ->get();

        $tenantConnections = app(TenantConnectionManager::class);
        $results = collect();

        foreach ($tenants as $tenant) {
            if ($results->count() >= $limit) {
                break;
            }

            $sedeName = $this->resolveSedeName($tenant);
            $tenantResults = $tenantConnections->runForTenant(
                $tenant,
                fn () => $this->searchInventoryTenantCatalog($type, $term, $limit)
            );

            $results = $results->concat(
                $tenantResults->map(function (array $result) use ($tenant, $sedeName) {
                    $redirect = $result['type'] === 'good'
                        ? "/group/{$result['group_id']}/inventory/{$result['inventory_id']}"
                        : "/group/{$result['group_id']}";

                    return array_merge($result, [
                        'sede_name' => $sedeName,
                        'tenant_slug' => $tenant->slug,
                        'url' => route('portal.switch', [
                            'slug' => $tenant->slug,
                            'redirect' => $redirect,
                            'inplace' => 1,
                        ]),
                        'update_history' => false,
                    ]);
                })
            );
        }

        return $results->take($limit)->values();
    }

    private function searchInventoryTenantCatalog(string $type, string $term, int $limit): Collection
    {
        $escapedTerm = $this->escapeLike($term);

        return match ($type) {
            'groups' => Group::query()
                ->withCount('inventories')
                ->where('name', 'like', "{$escapedTerm}%")
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(static fn (Group $group): array => [
                    'type' => 'group',
                    'title' => $group->name,
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'inventory_id' => null,
                    'inventory_name' => null,
                    'asset_id' => null,
                    'asset_type' => null,
                    'inventories_count' => $group->inventories_count,
                    'url' => route('inventory.inventories', $group->id),
                    'update_history' => true,
                ]),

            'inventories' => Inventory::query()
                ->with('group:id,name')
                ->select('id', 'name', 'group_id')
                ->where('name', 'like', "{$escapedTerm}%")
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(static fn (Inventory $inventory): array => [
                    'type' => 'inventory',
                    'title' => $inventory->name,
                    'group_id' => $inventory->group_id,
                    'group_name' => $inventory->group?->name ?? 'Sin grupo',
                    'inventory_id' => $inventory->id,
                    'inventory_name' => $inventory->name,
                    'asset_id' => null,
                    'asset_type' => null,
                    'url' => route('inventory.inventories', $inventory->group_id),
                    'update_history' => true,
                ]),

            'goods' => DB::connection('tenant')
                ->table('asset_inventory as ai')
                ->join('assets as a', 'a.id', '=', 'ai.asset_id')
                ->join('inventories as i', 'i.id', '=', 'ai.inventory_id')
                ->join('groups as g', 'g.id', '=', 'i.group_id')
                ->where('a.name', 'like', "%{$escapedTerm}%")
                ->select([
                    'a.id as asset_id',
                    'a.name as asset_name',
                    'a.type as asset_type',
                    'i.id as inventory_id',
                    'i.name as inventory_name',
                    'g.id as group_id',
                    'g.name as group_name',
                ])
                ->orderBy('a.name')
                ->orderBy('g.name')
                ->orderBy('i.name')
                ->limit($limit)
                ->get()
                ->map(static fn (object $row): array => [
                    'type' => 'good',
                    'title' => $row->asset_name,
                    'group_id' => $row->group_id,
                    'group_name' => $row->group_name,
                    'inventory_id' => $row->inventory_id,
                    'inventory_name' => $row->inventory_name,
                    'asset_id' => $row->asset_id,
                    'asset_type' => $row->asset_type,
                    'url' => route('inventory.goods', [
                        'groupId' => $row->group_id,
                        'inventoryId' => $row->inventory_id,
                    ]),
                    'update_history' => true,
                ]),
        };
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function resolveSedeName(Tenant $tenant): string
    {
        $rawName = trim((string) ($tenant->branding?->sede_name ?: $tenant->name ?: $tenant->slug));
        $normalized = preg_replace('/^sede\s+/iu', '', $rawName);

        return $normalized ?: ucfirst($tenant->slug);
    }
}
