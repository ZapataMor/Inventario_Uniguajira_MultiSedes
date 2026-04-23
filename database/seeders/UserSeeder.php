<?php

namespace Database\Seeders;

use App\Models\Central\Tenant;
use App\Models\Central\UserTenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = '12345678';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = $this->seedUser([
            'name' => 'Recursos Fisicos',
            'username' => 'recursosfisicos',
            'email' => 'recursosfisicos@uniguajira.edu.co',
            'role' => 'administrador',
            'global_role' => 'super_administrador',
        ]);

        $this->syncAllTenantMemberships($superAdmin);

        $tenantUser = $this->tenantUserForCurrentDatabase();

        if (! $tenantUser) {
            return;
        }

        foreach ($tenantUser as $userData) {
            $user = $this->seedUser($userData);
            $this->syncTenantMembership($user, $userData['role']);
        }
    }

    /**
     * @param  array{name: string, username: string, email: string, role: string, global_role?: string|null}  $userData
     */
    protected function seedUser(array $userData): User
    {
        return User::updateOrCreate(
            ['email' => $userData['email']],
            [
                'name' => $userData['name'],
                'username' => $userData['username'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => $userData['role'],
                'global_role' => $userData['global_role'] ?? null,
            ]
        );
    }

    /**
     * @return array<int, array{name: string, username: string, email: string, role: string}>|null
     */
    protected function tenantUserForCurrentDatabase(): ?array
    {
        $tenant = app(TenantContext::class)->get();
        $slug = $tenant?->slug ?? $this->inferTenantSlugFromDatabase((string) DB::connection()->getDatabaseName());

        return match ($slug) {
            'maicao' => [
                [
                    'name' => 'Recursos Fisicos Maicao',
                    'username' => 'recursosfisicosmaicao',
                    'email' => 'recursosfisicosmaicao@uniguajira.edu.co',
                    'role' => 'administrador',
                ],
                [
                    'name' => 'Consultor Maicao',
                    'username' => 'consultormaicao',
                    'email' => 'consultormaicao@uniguajira.edu.co',
                    'role' => 'consultor',
                ],
            ],
            'villanueva' => [
                [
                    'name' => 'Recursos Fisicos Villanueva',
                    'username' => 'recursosfisicosvillanueva',
                    'email' => 'recursosfisicosvillanueva@uniguajira.edu.co',
                    'role' => 'administrador',
                ],
                [
                    'name' => 'Consultor Villanueva',
                    'username' => 'consultorvillanueva',
                    'email' => 'consultorvillanueva@uniguajira.edu.co',
                    'role' => 'consultor',
                ],
            ],
            'fonseca' => [
                [
                    'name' => 'Recursos Fisicos Fonseca',
                    'username' => 'recursosfisicosfonseca',
                    'email' => 'recursosfisicosfonseca@uniguajira.edu.co',
                    'role' => 'administrador',
                ],
                [
                    'name' => 'Consultor Fonseca',
                    'username' => 'consultorfonseca',
                    'email' => 'consultorfonseca@uniguajira.edu.co',
                    'role' => 'consultor',
                ],
            ],
            default => null,
        };
    }

    protected function syncAllTenantMemberships(User $user): void
    {
        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            UserTenant::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                ],
                [
                    'role' => 'administrador',
                    'is_active' => true,
                ]
            );
        }
    }

    protected function syncTenantMembership(User $user, string $role): void
    {
        $tenant = app(TenantContext::class)->get();

        if (! $tenant) {
            $database = DB::connection()->getDatabaseName();
            $slug = $this->inferTenantSlugFromDatabase((string) $database);

            $tenant = Tenant::query()
                ->where(function ($query) use ($slug, $database) {
                    if ($slug) {
                        $query->where('slug', $slug)
                            ->orWhere('database', $database);
                    } else {
                        $query->where('database', $database);
                    }
                })
                ->first();
        }

        if (! $tenant) {
            return;
        }

        UserTenant::updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'role' => $role,
                'is_active' => true,
            ]
        );
    }

    protected function inferTenantSlugFromDatabase(string $database): ?string
    {
        $database = mb_strtolower(trim($database));

        if ($database === '') {
            return null;
        }

        foreach (['inventario_', 'inv_'] as $prefix) {
            if (str_starts_with($database, $prefix)) {
                return substr($database, strlen($prefix));
            }
        }

        if (preg_match('/(?:^|[_-])inv[_-]([a-z0-9]+)$/', $database, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(?:^|[_-])([a-z0-9]+)$/', $database, $matches)) {
            $candidate = $matches[1];

            try {
                if (Tenant::query()->where('slug', $candidate)->exists()) {
                    return $candidate;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
