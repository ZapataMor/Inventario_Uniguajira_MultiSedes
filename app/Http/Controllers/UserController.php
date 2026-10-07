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

        if (! $request->user()->isAdministrator()) {
            return $this->jsonError('No tienes permisos para crear usuarios.', 403);
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
     *
     * - Sede: administrador o super administrador editan usuarios de la sede activa.
     * - Portal: el super administrador edita usuarios del portal o de cualquier sede
     *   enviando `target_scope` (`portal` o `tenant:{id}`).
     *
     * Si se cambia la contrasena, el hash se replica en las demas sedes (y en la
     * base central) donde exista el mismo correo, para que funcione igual en
     * cualquier subdominio.
     */
    public function update(Request $request)
    {
        $this->autorizarGestionUsuarios();

        try {
            if ($this->isPortalManagementContext($request)) {
                return $this->updateFromPortal($request);
            }

            if (! $request->user()->isAdministrator()) {
                return $this->jsonError('No tienes permisos para editar usuarios.', 403);
            }

            $validated = $request->validate([
                'id' => ['required', 'integer'],
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['nullable', 'string', 'min:6'],
                'role' => ['nullable', Rule::in(array_merge(self::BASE_ROLES, User::GLOBAL_LEVELS))],
            ]);

            $user = User::find($validated['id']);

            if (! $user) {
                return $this->jsonError('Usuario no encontrado.', 404);
            }

            $this->assertUniqueIdentity($user, $validated);

            return $this->applyUserUpdate($user, $validated, tenant()?->id);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error actualizando usuario', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->jsonError('Ocurrio un error al actualizar el usuario.', 500);
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
     * Edita desde el portal un usuario del portal (central) o de una sede.
     */
    private function updateFromPortal(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['nullable', Rule::in(array_merge(self::BASE_ROLES, User::GLOBAL_LEVELS))],
            'target_scope' => ['required', 'string'],
        ]);

        // Los usuarios de sede los edita un super administrador-administrador; los
        // super administradores, solo el principal (se valida en applyUserUpdate).
        if ($validated['target_scope'] !== 'portal' && ! $request->user()->hasGlobalWriteAccess()) {
            return $this->jsonError('Tu nivel de super administrador es de solo consulta.', 403);
        }

        $centralConnection = config('tenancy.central_connection', 'central');

        if ($validated['target_scope'] === 'portal') {
            if (! Schema::connection($centralConnection)->hasTable('users')) {
                return $this->jsonError('El portal no tiene usuarios propios; edita al usuario desde su sede.', 422);
            }

            $user = User::on($centralConnection)->find($validated['id']);

            if (! $user) {
                return $this->jsonError('Usuario no encontrado.', 404);
            }

            $this->assertUniqueIdentity($user, $validated);

            return $this->applyUserUpdate($user, $validated, null);
        }

        if (! preg_match('/^tenant:(\d+)$/', $validated['target_scope'], $matches)) {
            return $this->jsonError('Selecciona una sede valida.', 422);
        }

        $tenant = Tenant::query()
            ->where('id', (int) $matches[1])
            ->where('is_active', true)
            ->with('branding')
            ->first();

        if (! $tenant) {
            return $this->jsonError('La sede seleccionada no esta disponible.', 422);
        }

        return app(TenantConnectionManager::class)->runForTenant(
            $tenant,
            function (Tenant $tenant) use ($validated) {
                $user = User::on('tenant')->find($validated['id']);

                if (! $user) {
                    return $this->jsonError('Usuario no encontrado en la sede seleccionada.', 404);
                }

                $this->assertUniqueIdentity($user, $validated);

                return $this->applyUserUpdate($user, $validated, $tenant->id);
            }
        );
    }

    /**
     * Aplica los cambios al usuario (en la conexion con la que fue cargado),
     * registra la auditoria y replica la contrasena si cambio.
     */
    private function applyUserUpdate(User $user, array $validated, ?int $tenantId)
    {
        $actor = auth()->user();
        $targetIsSuperAdmin = $user->isSuperAdmin();
        $requestedRole = $validated['role'] ?? null;
        $requestsGlobalLevel = in_array($requestedRole, User::GLOBAL_LEVELS, true);

        if ($targetIsSuperAdmin && ! $actor->canManageGlobalLevels()) {
            return $this->jsonError('Solo el super administrador principal puede modificar a un super administrador.', 403);
        }

        if ($requestsGlobalLevel && ! $targetIsSuperAdmin) {
            return $this->jsonError('Para otorgar un nivel de super administrador, crea el usuario desde el portal con el alcance "Portal".', 422);
        }

        $changesRole = ! $targetIsSuperAdmin
            && ! empty($requestedRole)
            && $requestedRole !== $user->role;

        // El principal siempre conserva el nivel administrador.
        $changesGlobalLevel = $targetIsSuperAdmin
            && $requestsGlobalLevel
            && ! $user->isRootAdmin()
            && $requestedRole !== $user->globalLevel();

        if ($changesGlobalLevel && ! $this->userTableSupportsGlobalRole($user->getConnectionName())) {
            return $this->jsonError('Esta base no admite niveles de super administrador.', 422);
        }

        $isSelf = $actor->getKey() === $user->getKey()
            && $actor->getConnectionName() === $user->getConnectionName();

        if ($changesRole && $isSelf) {
            return $this->jsonError('No puedes cambiar tu propio rol.', 422);
        }

        $oldEmail = $user->email;
        $oldValues = [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->effectiveRole(),
        ];

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];

        // A los super administradores solo se les cambia el nivel global; a los demas, el rol de sede.
        if (! $targetIsSuperAdmin && ! empty($requestedRole)) {
            $user->role = $requestedRole;
        }

        if ($changesGlobalLevel) {
            $user->global_role = $requestedRole;
        }

        $passwordHash = null;
        if (! empty($validated['password'])) {
            $passwordHash = Hash::make($validated['password']);
            $user->password = $passwordHash;
        }

        $user->save();

        if ($tenantId !== null && ! $targetIsSuperAdmin) {
            $this->syncTenantMembership($tenantId, $user->id, $user->role);
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

        $emails = array_values(array_unique([$oldEmail, $user->email]));

        $syncedIn = 0;
        if ($passwordHash !== null) {
            $syncedIn = $this->propagateAttributes(['password' => $passwordHash], $emails);
        }

        if ($changesGlobalLevel) {
            $levelSyncedIn = $this->propagateAttributes(['global_role' => $requestedRole], $emails, onlyGlobalUsers: true);
            $syncedIn = max($syncedIn, $levelSyncedIn);
        }

        $replicated = $changesGlobalLevel ? 'El nivel' : 'La contrasena';

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => $syncedIn > 0
                ? "Usuario actualizado. {$replicated} tambien se aplico en {$syncedIn} sede(s) donde existe este correo."
                : 'Usuario actualizado correctamente.',
        ]);
    }

    /**
     * Valida que usuario y correo no choquen con otro usuario de la MISMA conexion
     * (la regla `unique` usa la conexion por defecto, que en el portal es la central).
     */
    private function assertUniqueIdentity(User $user, array $validated): void
    {
        $taken = fn (string $column, string $value): bool => User::on($user->getConnectionName())
            ->where($column, $value)
            ->whereKeyNot($user->getKey())
            ->exists();

        $errors = [];

        if ($taken('username', $validated['username'])) {
            $errors['username'] = 'El nombre de usuario ya esta en uso.';
        }

        if ($taken('email', $validated['email'])) {
            $errors['email'] = 'El correo ya esta en uso.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Copia atributos (hash de contrasena, nivel global) a los usuarios con esos
     * correos en cada sede activa y en la base central. Usa el query builder para
     * no volver a hashear.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $emails
     * @param  bool  $onlyGlobalUsers  solo filas que ya son super administrador (no promueve usuarios de sede)
     * @return int sedes donde se encontro y actualizo al usuario
     */
    private function propagateAttributes(array $attributes, array $emails, bool $onlyGlobalUsers = false): int
    {
        $touched = 0;
        $tenantConnections = app(TenantConnectionManager::class);

        $update = fn (string $connection): int => User::on($connection)
            ->whereIn('email', $emails)
            ->when($onlyGlobalUsers, fn ($query) => $query->whereNotNull('global_role'))
            ->update($attributes);

        $tenants = Tenant::query()->where('is_active', true)->orderBy('id')->get();

        foreach ($tenants as $tenant) {
            try {
                $rows = $tenantConnections->runForTenant($tenant, fn () => $update('tenant'));

                $touched += $rows > 0 ? 1 : 0;
            } catch (\Throwable $e) {
                Log::warning('No se pudo replicar el cambio de usuario en la sede', [
                    'tenant' => $tenant->slug,
                    'attributes' => array_keys($attributes),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $centralConnection = config('tenancy.central_connection', 'central');

        try {
            if (Schema::connection($centralConnection)->hasTable('users')) {
                $update($centralConnection);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo replicar el cambio de usuario en la base central', [
                'attributes' => array_keys($attributes),
                'message' => $e->getMessage(),
            ]);
        }

        return $touched;
    }

    private function jsonError(string $message, int $status)
    {
        return response()->json([
            'success' => false,
            'type' => 'error',
            'message' => $message,
        ], $status);
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
                'role' => ['nullable', Rule::in(array_merge(self::BASE_ROLES, User::GLOBAL_LEVELS))],
            ]);

            $actor = $request->user();

            if ($validated['target_scope'] === 'portal') {
                if (! $actor->canManageGlobalLevels()) {
                    return $this->jsonError('Solo el super administrador principal puede crear super administradores.', 403);
                }

                $level = (string) ($validated['role'] ?? '');
                if (! in_array($level, User::GLOBAL_LEVELS, true)) {
                    return $this->jsonError('Selecciona el nivel del super administrador.', 422);
                }

                return $this->storePortalSuperAdmin($validated, $level);
            }

            if (! $actor->hasGlobalWriteAccess()) {
                return $this->jsonError('Tu nivel de super administrador es de solo consulta.', 403);
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
    private function storePortalSuperAdmin(array $validated, string $level)
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
        $failedSedes = [];
        $tenantConnections = app(TenantConnectionManager::class);

        // Validar en todas las sedes antes de escribir para no dejar al usuario a medio crear.
        foreach ($tenants as $tenantData) {
            try {
                $conflict = $tenantConnections->runForTenant(
                    $tenantData['tenant'],
                    fn () => User::on('tenant')
                        ->where('username', $validated['username'])
                        ->where('email', '!=', $validated['email'])
                        ->exists()
                );
            } catch (\Throwable $e) {
                $conflict = false;
            }

            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => "El nombre de usuario ya existe en la sede {$tenantData['name']}.",
                ], 422);
            }
        }

        $this->syncCentralSuperAdminIfAvailable($validated, $level);

        foreach ($tenants as $tenantData) {
            try {
                $tenantConnections->runForTenant(
                    $tenantData['tenant'],
                    // Sin membresia en user_tenant: el nivel global ya da acceso a todas las
                    // sedes y el id local de la sede dejaria filas huerfanas en la central.
                    function (Tenant $tenant) use ($validated, $level, &$created, &$updated) {
                        $supportsGlobalRole = $this->userTableSupportsGlobalRole('tenant');

                        $existing = User::on('tenant')
                            ->where('email', $validated['email'])
                            ->first();

                        $payload = $this->buildSuperAdminPayload($validated, $supportsGlobalRole, $level);

                        if ($existing) {
                            $existing->fill($payload)->save();
                            $updated++;
                        } else {
                            User::on('tenant')->create(array_merge($payload, [
                                'email' => $validated['email'],
                            ]));
                            $created++;
                        }
                    }
                );
            } catch (\Throwable $e) {
                // El login en sede sincroniza al super administrador desde la central si falta aqui.
                Log::error('No se pudo registrar el super administrador en la sede', [
                    'tenant' => $tenantData['slug'],
                    'message' => $e->getMessage(),
                ]);

                $failedSedes[] = $tenantData['name'];
            }
        }

        $syncedCount = $tenants->count() - count($failedSedes);
        $message = "Super administrador registrado en portal y sincronizado en {$syncedCount} sede(s).";

        if ($failedSedes !== []) {
            $message .= ' No se pudo sincronizar en: '.implode(', ', $failedSedes).'; se sincronizara al iniciar sesion en esa sede.';
        }

        return response()->json([
            'success' => true,
            'type' => $failedSedes === [] ? 'success' : 'warning',
            'message' => $message,
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
    private function syncCentralSuperAdminIfAvailable(array $validated, string $level): void
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

        $payload = $this->buildSuperAdminPayload($validated, $supportsGlobalRole, $level);

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
                    $innerQuery->whereIn('global_role', User::GLOBAL_LEVELS)
                        ->orWhere('role', 'super_administrador')
                        ->orWhere('email', User::ROOT_ADMIN_EMAIL);
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
    private function buildSuperAdminPayload(array $validated, bool $supportsGlobalRole, string $level): array
    {
        $payload = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $supportsGlobalRole ? 'consultor' : 'super_administrador',
        ];

        if ($supportsGlobalRole) {
            $payload['global_role'] = $level;
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
