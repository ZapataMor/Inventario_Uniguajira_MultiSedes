<?php

namespace App\Support\Tenancy;

use App\Models\Central\Tenant;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Traspaso de sesion del portal central a una sede.
 *
 * El portal emite un token cifrado, de corta duracion y de un solo uso. La sede lo
 * canjea, ubica (o crea) al super administrador en su propia base y abre una sesion
 * de sede. Asi la sede nunca reutiliza la sesion del portal, cuyo id de usuario
 * pertenece a la base central y no a la de la sede.
 */
class PortalHandoff
{
    private const TTL_SECONDS = 60;

    public function issue(User $centralUser, Tenant $tenant, string $redirectPath): string
    {
        return Crypt::encrypt([
            'email' => $centralUser->email,
            'tenant_id' => (int) $tenant->id,
            'redirect' => $redirectPath,
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->getTimestamp(),
            'nonce' => Str::random(40),
        ]);
    }

    /**
     * @return array{user: User, redirect: string}|null
     */
    public function redeem(string $token, Tenant $tenant): ?array
    {
        try {
            $payload = Crypt::decrypt($token);
        } catch (DecryptException) {
            return null;
        }

        if (! is_array($payload)
            || (int) ($payload['tenant_id'] ?? 0) !== (int) $tenant->id
            || (int) ($payload['expires_at'] ?? 0) < now()->getTimestamp()
            || empty($payload['email'])
            || empty($payload['nonce'])) {
            return null;
        }

        // Un solo uso: si el nonce ya se canjeo, el token no vuelve a servir.
        if (! Cache::add('portal-handoff:'.$payload['nonce'], true, self::TTL_SECONDS * 2)) {
            return null;
        }

        $centralUser = User::on(config('tenancy.central_connection', 'central'))
            ->where('email', $payload['email'])
            ->first();

        if (! $centralUser?->isGlobalAdmin()) {
            return null;
        }

        try {
            $tenantUser = $this->syncIntoTenant($centralUser);
        } catch (\Throwable $e) {
            Log::error('Acceso desde portal: no se pudo sincronizar el super administrador en la sede.', [
                'tenant' => $tenant->slug,
                'email' => $centralUser->email,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $tenantUser?->isGlobalAdmin()) {
            return null;
        }

        return ['user' => $tenantUser, 'redirect' => (string) ($payload['redirect'] ?? '/home')];
    }

    /**
     * Crea o actualiza (por correo) al super administrador en la sede activa. La base
     * central es la fuente de verdad del nivel global y, al crearlo, de su contrasena.
     */
    private function syncIntoTenant(User $centralUser): ?User
    {
        if (! Schema::connection('tenant')->hasColumn('users', 'global_role')) {
            throw new \RuntimeException('La sede no tiene la columna users.global_role; ejecuta tenant:migrate en esa sede.');
        }

        $users = DB::connection('tenant')->table('users');
        $existing = (clone $users)->where('email', $centralUser->email)->first();
        $level = $centralUser->globalLevel();

        if ($existing) {
            if ($existing->global_role !== $level) {
                (clone $users)->where('id', $existing->id)->update([
                    'global_role' => $level,
                    'updated_at' => now(),
                ]);
            }

            return User::on('tenant')->find($existing->id);
        }

        $usernameTaken = (clone $users)->where('username', $centralUser->username)->exists();

        $id = (clone $users)->insertGetId([
            'name' => $centralUser->name,
            'username' => $usernameTaken ? $centralUser->email : $centralUser->username,
            'email' => $centralUser->email,
            'password' => $centralUser->getAuthPassword(),
            'role' => 'consultor',
            'global_role' => $level,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('Acceso desde portal: super administrador creado en la sede.', [
            'tenant' => tenant('slug'),
            'email' => $centralUser->email,
        ]);

        return User::on('tenant')->find($id);
    }
}
