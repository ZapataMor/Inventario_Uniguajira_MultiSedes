<?php

namespace Database\Seeders;

use App\Models\Central\Tenant;
use App\Models\Central\UserTenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrador',
                'username' => 'administrador',
                'email' => 'admin@email.com',
                'password' => '1234',
                'role' => 'administrador',
            ],
            [
                'name' => 'Luis',
                'username' => 'luis',
                'email' => 'luis@email.com',
                'password' => '1234',
                'role' => 'administrador',
            ],
            [
                'name' => 'Renzo',
                'username' => 'renzo',
                'email' => 'renzo@email.com',
                'password' => '1234',
                'role' => 'administrador',
            ],
            [
                'name' => 'Kevin',
                'username' => 'kevin',
                'email' => 'kevin@example.com',
                'password' => '12345678',
                'role' => 'administrador',
            ],
            [
                'name' => 'Consultor',
                'username' => 'consultor',
                'email' => 'consultor@email.com',
                'password' => 'consul',
                'role' => 'consultor',
            ],
            [
                'name' => 'Consultora',
                'username' => 'consultora',
                'email' => 'consultora@email.com',
                'password' => 'consul',
                'role' => 'consultor',
            ],
            [
                'name' => 'Daniel',
                'username' => 'Danie1l6',
                'email' => 'daniel@email.com',
                'password' => '1234',
                'role' => 'administrador',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => Hash::make($userData['password']),
                    'role' => $userData['role'],
                ]
            );

            $this->syncTenantMembership($user, $userData['role']);
        }

        // Super Administrador — acceso al portal y a todas las sedes
        $superAdmin = User::whereIn('email', ['admin@example.edu.co', 'admin@example.com'])->first();

        if ($superAdmin) {
            $superAdmin->fill([
                'name' => 'Super Administrador',
                'username' => 'superadmin',
                'email' => 'admin@example.edu.co',
                'password' => Hash::make('1234'),
                'role' => 'administrador',
                'global_role' => 'super_administrador',
            ]);
            $superAdmin->save();
        } else {
            $superAdmin = User::create([
                'name' => 'Super Administrador',
                'username' => 'superadmin',
                'email' => 'admin@example.edu.co',
                'password' => Hash::make('1234'),
                'role' => 'administrador',
                'global_role' => 'super_administrador',
            ]);
        }

        $this->syncAllTenantMemberships($superAdmin);

        $tenantAdmin = $this->tenantAdminUser();

        if ($tenantAdmin) {
            $user = User::updateOrCreate(
                ['email' => $tenantAdmin['email']],
                [
                    'name' => $tenantAdmin['name'],
                    'username' => $tenantAdmin['username'],
                    'password' => Hash::make($tenantAdmin['password']),
                    'role' => 'administrador',
                ]
            );

            $this->syncTenantMembership($user, 'administrador');
        }
    }

    protected function tenantAdminUser(): ?array
    {
        $database = DB::connection()->getDatabaseName();
        $slug = $this->inferTenantSlugFromDatabase((string) $database);

        return match ($slug) {
            'maicao' => [
                'name' => 'Administrador Maicao',
                'username' => 'admin.maicao',
                'email' => 'maicao@uniguajira.edu.co',
                'password' => '1234',
            ],
            'fonseca' => [
                'name' => 'Administrador Fonseca',
                'username' => 'admin.fonseca',
                'email' => 'fonseca@uniguajira.edu.co',
                'password' => '1234',
            ],
            'villanueva' => [
                'name' => 'Administrador Villanueva',
                'username' => 'admin.villanueva',
                'email' => 'villanueva@uniguajira.edu.co',
                'password' => '1234',
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

    protected function syncTenantMembership(User $user, string $role = 'administrador'): void
    {
        // Prioridad 1: cuando el seeder corre desde tenant:migrate, el contexto
        // ya trae el tenant activo y evita depender del nombre exacto de la DB.
        $tenantFromContext = app(TenantContext::class)->get();

        if ($tenantFromContext) {
            UserTenant::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenantFromContext->id,
                ],
                [
                    'role' => $role,
                    'is_active' => true,
                ]
            );

            return;
        }

        $database = DB::connection()->getDatabaseName();

        // Fallback local: permitir alias de BD (inv_maicao / inventario_maicao).
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

        // Compatibilidad con nombres personalizados, por ejemplo:
        // u946584072_inv_maicao -> maicao
        if (preg_match('/(?:^|[_-])inv[_-]([a-z0-9]+)$/', $database, $matches)) {
            return $matches[1];
        }

        // Fallback: tomar el ultimo segmento y validarlo contra tenants.slug.
        if (preg_match('/(?:^|[_-])([a-z0-9]+)$/', $database, $matches)) {
            $candidate = $matches[1];

            try {
                if (Tenant::query()->where('slug', $candidate)->exists()) {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                // Silencioso: en entornos donde central aun no existe.
            }
        }

        return null;
    }
}
