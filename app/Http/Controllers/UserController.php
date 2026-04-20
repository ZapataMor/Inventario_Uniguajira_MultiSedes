<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Central\Tenant;
use App\Models\Central\UserTenant;
use App\Models\User;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    private const BASE_ROLES = ['administrador', 'consultor'];

    /**
     * Solo administradores de sede y super administradores pueden entrar al modulo.
     */
    private function autorizarGestionUsuarios(): void
    {
        $user = auth()->user();

        abort_if(! $user || (! $user->isAdministrator() && ! $user->isSuperAdmin()), 403);
    }

    /**
     * Determina si estamos en el portal central usando un super administrador.
     */
    private function isPortalManagementContext(Request $request): bool
    {
        return ! tenant() && $request->user()?->isSuperAdmin();
    }

    /**
     * Vista principal (listado).
     */
    public function index(Request $request)
    {
        $this->autorizarGestionUsuarios();

        $isPortalUserCatalog = $this->isPortalManagementContext($request);
        $usersByScope = collect();
        $availableTenants = collect();

        if ($isPortalUserCatalog) {
            $availableTenants = $this->getActiveTenants();
            $usersByScope = $this->getUsersByScopeForPortal($availableTenants);
            $users = $usersByScope->flatMap(fn (array $scopeData) => $scopeData['users'])->values();
        } else {
            $users = User::orderBy('id', 'desc')->get();
        }

        if ($request->ajax()) {
            return view('users.index', compact('users', 'usersByScope', 'isPortalUserCatalog', 'availableTenants'))
                ->renderSections()['content'];
        }

        return view('users.index', compact('users', 'usersByScope', 'isPortalUserCatalog', 'availableTenants'));
    }

    /**
     * API: Crear usuario
     * POST /api/users/store
     */
    public function store(Request $request)
    {
        $this->autorizarGestionUsuarios();

        if ($this->isPortalManagementContext($request)) {
            return $this->storeFromPortal($request);
        }

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'role' => ['required', Rule::in(self::BASE_ROLES)],
            ]);

            $payload = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ];

            if ($this->userTableSupportsGlobalRole()) {
                $payload['global_role'] = null;
            }

            $user = User::create($payload);

            if (tenant()) {
                $this->syncTenantMembership(tenant()->id, $user->id, $validated['role']);
            }

            ActivityLogger::created(User::class, $user->id, $user->name);

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Usuario creado correctamente.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrio un error al crear el usuario.',
            ], 500);
        }
    }

    /**
     * API: Actualizar usuario
     * POST /api/users/update
     */
    public function update(Request $request)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        try {
            $validated = $request->validate([
                'id' => ['required', 'exists:users,id'],
                'name' => ['required', 'string', 'max:255'],
                'username' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users', 'username')->ignore($request->id),
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($request->id),
                ],
                'password' => ['nullable', 'string', 'min:6'],
                'role' => ['required', Rule::in(self::BASE_ROLES)],
            ]);

            $user = User::findOrFail($validated['id']);

            if (auth()->id() === $user->id && $validated['role'] !== $user->role) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'No puedes cambiar tu propio rol.',
                ], 422);
            }

            $oldValues = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->effectiveRole(),
            ];

            $user->name = $validated['name'];
            $user->username = $validated['username'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];

            if ($this->userTableSupportsGlobalRole()) {
                $user->global_role = null;
            }

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            if (tenant()) {
                $this->syncTenantMembership(tenant()->id, $user->id, $validated['role']);
            }

            ActivityLogger::updated(
                User::class,
                $user->id,
                $user->name,
                $oldValues,
                [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->effectiveRole(),
                ]
            );

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Usuario actualizado correctamente.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrio un error al actualizar el usuario.',
            ], 500);
        }
    }

    /**
     * API: Eliminar usuario
     * DELETE /api/users/delete/{id}
     */
    public function destroy($id)
    {
        abort_if(! auth()->user()?->isAdministrator(), 403);

        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if ($user->name === 'Administrador') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario administrador no puede ser eliminado.',
            ], 403);
        }

        if ($user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario super administrador no puede ser eliminado.',
            ], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propio usuario',
            ], 403);
        }

        $user->delete();

        if (tenant()) {
            UserTenant::on('central')
                ->where('tenant_id', tenant()->id)
                ->where('user_id', $id)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    /**
     * Crea usuarios desde el portal central.
     * - Portal: solo super administradores.
     * - Sede: administrador o consultor para la sede seleccionada.
     */
    private function storeFromPortal(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:6'],
                'target_scope' => ['required', 'string'],
                'role' => ['nullable', Rule::in(array_merge(self::BASE_ROLES, ['super_administrador']))],
            ]);

            if ($validated['target_scope'] === 'portal') {
                return $this->storePortalSuperAdmin($validated);
            }

            if (! preg_match('/^tenant:(\d+)$/', $validated['target_scope'], $matches)) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'Selecciona una sede valida.',
                ], 422);
            }

            $tenantId = (int) $matches[1];
            $tenant = Tenant::query()
                ->where('id', $tenantId)
                ->where('is_active', true)
                ->with('branding')
                ->first();

            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'La sede seleccionada no esta disponible.',
                ], 422);
            }

            $role = (string) ($validated['role'] ?? 'consultor');
            if (! in_array($role, self::BASE_ROLES, true)) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'El rol para sede debe ser Administrador o Consultor.',
                ], 422);
            }

            return $this->storeTenantUserFromPortal($tenant, $validated, $role);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error creando usuario desde el portal', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Ocurrio un error al crear el usuario desde el portal.',
            ], 500);
        }
    }

    /**
     * Crea o actualiza un super administrador en todas las sedes activas.
     */
    private function storePortalSuperAdmin(array $validated)
    {
        $tenants = $this->getActiveTenants();

        if ($tenants->isEmpty()) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'No hay sedes activas para registrar el super administrador.',
            ], 422);
        }

        $created = 0;
        $updated = 0;
        $tenantConnections = app(TenantConnectionManager::class);

        $this->syncCentralSuperAdminIfAvailable($validated);

        foreach ($tenants as $tenantData) {
            $result = $tenantConnections->runForTenant(
                $tenantData['tenant'],
                function (Tenant $tenant) use ($tenantData, $validated, &$created, &$updated) {
                    $supportsGlobalRole = $this->userTableSupportsGlobalRole('tenant');

                    $existingByUsername = User::on('tenant')
                        ->where('username', $validated['username'])
                        ->first();

                    if ($existingByUsername && $existingByUsername->email !== $validated['email']) {
                        return response()->json([
                            'success' => false,
                            'type' => 'error',
                            'message' => "El nombre de usuario ya existe en la sede {$tenantData['name']}.",
                        ], 422);
                    }

                    $existing = User::on('tenant')
                        ->where('email', $validated['email'])
                        ->first();

                    $payload = $this->buildSuperAdminPayload($validated, $supportsGlobalRole);

                    if ($existing) {
                        $existing->fill($payload);
                        $existing->save();
                        $user = $existing;
                        $updated++;
                    } else {
                        $user = User::on('tenant')->create(array_merge($payload, [
                            'email' => $validated['email'],
                        ]));
                        $created++;
                    }

                    $this->syncTenantMembership($tenantData['id'], $user->id, 'consultor');

                    return null;
                }
            );

            if ($result !== null) {
                return $result;
            }
        }

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => "Super administrador registrado en portal y sincronizado en {$tenants->count()} sede(s).",
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    /**
     * Crea usuario de sede desde el portal central.
     */
    private function storeTenantUserFromPortal(Tenant $tenant, array $validated, string $role)
    {
        $tenantConnections = app(TenantConnectionManager::class);

        return $tenantConnections->runForTenant($tenant, function (Tenant $tenant) use ($validated, $role) {
            $supportsGlobalRole = $this->userTableSupportsGlobalRole('tenant');

            $existsByEmail = User::on('tenant')->where('email', $validated['email'])->exists();
            if ($existsByEmail) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'Ya existe un usuario con ese correo en la sede seleccionada.',
                ], 422);
            }

            $existsByUsername = User::on('tenant')->where('username', $validated['username'])->exists();
            if ($existsByUsername) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'Ya existe un usuario con ese nombre de usuario en la sede seleccionada.',
                ], 422);
            }

            $user = User::on('tenant')->create(array_merge(
                $this->buildTenantUserPayload($validated, $role, $supportsGlobalRole),
                ['email' => $validated['email']]
            ));

            $this->syncTenantMembership($tenant->id, $user->id, $role);

            ActivityLogger::created(User::class, $user->id, $user->name);

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => "Usuario creado correctamente para la sede {$this->resolveSedeName($tenant)}.",
            ]);
        });
    }

    /**
     * Obtiene usuarios del portal (solo super administradores) y de cada sede activa.
     */
    private function getUsersByScopeForPortal(Collection $tenants): Collection
    {
        $tenantConnections = app(TenantConnectionManager::class);

        $tenantScopes = $tenants->map(function (array $tenantData) use ($tenantConnections): array {
            return $tenantConnections->runForTenant($tenantData['tenant'], function (Tenant $tenant) use ($tenantData): array {
                try {
                    $users = User::on('tenant')->orderBy('id', 'desc')->get();
                } catch (\Throwable $e) {
                    $users = collect();
                }

                return [
                    'scope' => 'sede',
                    'tenant_id' => $tenantData['id'],
                    'tenant_slug' => $tenantData['slug'],
                    'sede_name' => $tenantData['name'],
                    'dropdown_label' => "Usuarios sede {$tenantData['name']}",
                    'users' => $users,
                ];
            });
        });

        $portalUsers = $this->getCentralPortalUsers();

        if ($portalUsers->isEmpty()) {
            $portalUsers = $tenantScopes
                ->flatMap(fn (array $scopeData) => $scopeData['users'])
                ->filter(fn (User $user) => $user->isSuperAdmin())
                ->unique(fn (User $user) => mb_strtolower((string) $user->email))
                ->sortBy('name')
                ->values();
        }

        return collect([
            [
                'scope' => 'portal',
                'tenant_id' => null,
                'tenant_slug' => null,
                'sede_name' => 'Portal',
                'dropdown_label' => 'Usuarios del portal (Super Administradores)',
                'users' => $portalUsers,
            ],
        ])->concat($tenantScopes)->values();
    }

    /**
     * Sedes activas con metadatos minimos para vistas y operaciones.
     */
    private function getActiveTenants(): Collection
    {
        return Tenant::query()
            ->where('is_active', true)
            ->with('branding')
            ->orderBy('id')
            ->get()
            ->map(function (Tenant $tenant): array {
                return [
                    'id' => $tenant->id,
                    'tenant' => $tenant,
                    'slug' => $tenant->slug,
                    'database' => $tenant->database,
                    'name' => $this->resolveSedeName($tenant),
                ];
            })
            ->values();
    }

    /**
     * Sincroniza la membresia en la tabla central user_tenant.
     */
    private function syncTenantMembership(int $tenantId, int $userId, string $role): void
    {
        UserTenant::on('central')->updateOrCreate(
            [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ],
            [
                'role' => $role,
                'is_active' => true,
            ]
        );
    }

    /**
     * Si la base central tiene tabla users, sincroniza ahi el super administrador del portal.
     */
    private function syncCentralSuperAdminIfAvailable(array $validated): void
    {
        if (! Schema::connection('central')->hasTable('users')) {
            return;
        }

        $supportsGlobalRole = $this->userTableSupportsGlobalRole('central');

        $existingByUsername = User::on('central')
            ->where('username', $validated['username'])
            ->first();

        if ($existingByUsername && $existingByUsername->email !== $validated['email']) {
            throw ValidationException::withMessages([
                'username' => 'El nombre de usuario ya existe en el portal.',
            ]);
        }

        $payload = $this->buildSuperAdminPayload($validated, $supportsGlobalRole);

        $existing = User::on('central')
            ->where('email', $validated['email'])
            ->first();

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return;
        }

        User::on('central')->create(array_merge($payload, [
            'email' => $validated['email'],
        ]));
    }

    /**
     * Obtiene usuarios super administradores del portal desde la base central si existe.
     *
     * @return Collection<int, User>
     */
    private function getCentralPortalUsers(): Collection
    {
        if (! Schema::connection('central')->hasTable('users')) {
            return collect();
        }

        try {
            $query = User::on('central')->orderBy('name');

            if ($this->userTableSupportsGlobalRole('central')) {
                $query->where(function ($innerQuery) {
                    $innerQuery->where('global_role', 'super_administrador')
                        ->orWhere('role', 'super_administrador');
                });
            } else {
                $query->where('role', 'super_administrador');
            }

            return $query->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Verifica si la tabla users de una conexion contiene la columna global_role.
     */
    private function userTableSupportsGlobalRole(?string $connection = null): bool
    {
        $connection = $connection ?: config('database.default', 'tenant');

        try {
            if (! Schema::connection($connection)->hasTable('users')) {
                return false;
            }

            return Schema::connection($connection)->hasColumn('users', 'global_role');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Payload para super administrador compatible con esquemas con/sin global_role.
     */
    private function buildSuperAdminPayload(array $validated, bool $supportsGlobalRole): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $supportsGlobalRole ? 'consultor' : 'super_administrador',
        ];

        if ($supportsGlobalRole) {
            $payload['global_role'] = 'super_administrador';
        }

        return $payload;
    }

    /**
     * Payload para usuarios de sede compatible con esquemas con/sin global_role.
     */
    private function buildTenantUserPayload(array $validated, string $role, bool $supportsGlobalRole): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
        ];

        if ($supportsGlobalRole) {
            $payload['global_role'] = null;
        }

        return $payload;
    }

    private function resolveSedeName(Tenant $tenant): string
    {
        $rawName = trim((string) ($tenant->branding?->sede_name ?: $tenant->name ?: $tenant->slug));
        $normalized = preg_replace('/^sede\s+/iu', '', $rawName);

        return $normalized ?: ucfirst($tenant->slug);
    }
}
