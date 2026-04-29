<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\Central\Tenant;
use App\Models\Central\UserTenant;
use App\Models\User;
use App\Support\Tenancy\TenantConnectionManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::authenticateUsing(fn (Request $request) => $this->authenticateUser($request));
    }

    private function authenticateUser(Request $request): ?User
    {
        $login = trim((string) $request->input(Fortify::username()));
        $password = (string) $request->input('password');

        if ($login === '' || $password === '') {
            return null;
        }

        if (tenant()) {
            $user = $this->attemptUserOnConnection('tenant', $login, $password);

            if ($user) {
                $this->syncTenantMembershipForLocalUser($user);
                $request->session()->put('tenant_id', tenant('id'));
                $request->session()->put('auth_tenant_id', tenant('id'));
            }

            return $user;
        }

        $request->session()->forget(['tenant_id', 'auth_tenant_id']);

        $centralConnection = config('tenancy.central_connection', 'central');
        $centralUser = $this->attemptUserOnConnection($centralConnection, $login, $password);

        if ($centralUser?->isGlobalAdmin()) {
            return $this->normalizeCentralGlobalAdminFromTenants($centralUser, $password);
        }

        return $this->attemptGlobalAdminFromTenants($login, $password);
    }

    private function attemptUserOnConnection(string $connection, string $login, string $password): ?User
    {
        $user = User::on($connection)
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('username', $login);
            })
            ->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        return $user;
    }

    private function attemptGlobalAdminFromTenants(string $login, string $password): ?User
    {
        $tenantConnections = app(TenantConnectionManager::class);

        $tenants = Tenant::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($tenants as $tenant) {
            $tenantUser = $tenantConnections->runForTenant(
                $tenant,
                fn () => $this->attemptUserOnConnection('tenant', $login, $password)
            );

            if (! $tenantUser?->isGlobalAdmin()) {
                continue;
            }

            return $this->syncCentralUserFromTenant($tenantUser);
        }

        return null;
    }

    private function normalizeCentralGlobalAdminFromTenants(User $centralUser, string $password): User
    {
        $tenantConnections = app(TenantConnectionManager::class);

        $tenants = Tenant::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($tenants as $tenant) {
            $tenantUser = $tenantConnections->runForTenant(
                $tenant,
                fn () => $this->attemptUserOnConnection('tenant', (string) $centralUser->email, $password)
            );

            if ($tenantUser?->isGlobalAdmin()) {
                return $this->syncCentralUserFromTenant($tenantUser);
            }
        }

        return $centralUser;
    }

    private function syncCentralUserFromTenant(User $tenantUser): User
    {
        $centralConnection = config('tenancy.central_connection', 'central');
        $desiredId = (int) $tenantUser->getKey();
        $payload = [
            'name' => $tenantUser->name,
            'username' => $tenantUser->username,
            'password' => $tenantUser->password,
            'role' => $tenantUser->role ?: 'administrador',
            'global_role' => 'super_administrador',
        ];

        $centralUser = User::on($centralConnection)
            ->where('email', $tenantUser->email)
            ->first();

        $desiredIdIsAvailable = ! User::on($centralConnection)
            ->whereKey($desiredId)
            ->when($centralUser, fn ($query) => $query->whereKeyNot($centralUser->getKey()))
            ->exists();

        if ($centralUser && (int) $centralUser->getKey() !== $desiredId && $desiredIdIsAvailable) {
            DB::connection($centralConnection)->transaction(function () use ($centralUser, $desiredId, $payload, $centralConnection): void {
                UserTenant::on($centralConnection)
                    ->where('user_id', $centralUser->getKey())
                    ->update(['user_id' => $desiredId]);

                User::on($centralConnection)
                    ->whereKey($centralUser->getKey())
                    ->update(array_merge($payload, ['id' => $desiredId]));
            });

            $centralUser = User::on($centralConnection)->findOrFail($desiredId);
        } elseif (! $centralUser && $desiredIdIsAvailable) {
            $centralUser = new User;
            $centralUser->setConnection($centralConnection);
            $centralUser->forceFill(array_merge($payload, [
                'id' => $desiredId,
                'email' => $tenantUser->email,
            ]))->save();
        } else {
            $centralUser = User::on($centralConnection)->updateOrCreate(
                ['email' => $tenantUser->email],
                $payload
            );
        }

        Tenant::query()
            ->where('is_active', true)
            ->pluck('id')
            ->each(function (int $tenantId) use ($centralUser): void {
                UserTenant::on(config('tenancy.central_connection', 'central'))->updateOrCreate(
                    [
                        'user_id' => $centralUser->id,
                        'tenant_id' => $tenantId,
                    ],
                    [
                        'role' => 'administrador',
                        'is_active' => true,
                    ]
                );
            });

        return $centralUser;
    }

    private function syncTenantMembershipForLocalUser(User $user): void
    {
        $tenant = tenant();

        if (! $tenant || ! in_array($user->role, config('tenancy.tenant_roles', []), true)) {
            return;
        }

        UserTenant::on(config('tenancy.central_connection', 'central'))->updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
            ],
            [
                'role' => $user->role,
                'is_active' => true,
            ]
        );
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('livewire.auth.login'));
        Fortify::verifyEmailView(fn () => view('livewire.auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('livewire.auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('livewire.auth.confirm-password'));
        if (Features::enabled(Features::registration())) {
            // only register the view if the registration feature is active
            Fortify::registerView(fn () => view('livewire.auth.register'));
        }
        Fortify::resetPasswordView(fn () => view('livewire.auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('livewire.auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
