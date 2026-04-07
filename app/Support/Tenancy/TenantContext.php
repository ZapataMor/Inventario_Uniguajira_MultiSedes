<?php

namespace App\Support\Tenancy;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Singleton que mantiene el estado del tenant activo durante el request.
 *
 * Se resuelve una sola vez al inicio del request por el middleware
 * ResolveTenant y queda disponible vía app(TenantContext::class)
 * o el helper tenant().
 */
class TenantContext
{
    protected ?Tenant $tenant = null;

    protected bool $resolved = false;

    /**
     * Configuración base de la conexión tenant (según .env).
     */
    protected array $baseTenantConnection = [];

    /**
     * Conexión por defecto antes de aplicar tenancy.
     */
    protected ?string $baseDefaultConnection = null;

    /**
     * Establece el tenant activo y configura la conexión dinámica.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;

        if (empty($this->baseTenantConnection)) {
            $this->baseTenantConnection = config('database.connections.tenant', []);
        }

        if ($this->baseDefaultConnection === null) {
            $this->baseDefaultConnection = config('database.default');
        }

        $this->configureTenantConnection($tenant);
    }

    /**
     * Obtiene el tenant activo.
     */
    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Verifica si ya se resolvió el tenant para este request.
     */
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    /**
     * Devuelve el ID del tenant activo o null.
     */
    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Devuelve el slug del tenant activo o null.
     */
    public function slug(): ?string
    {
        return $this->tenant?->slug;
    }

    /**
     * Devuelve el nombre de la base de datos del tenant activo.
     */
    public function database(): ?string
    {
        return $this->tenant?->database;
    }

    /**
     * Verifica si estamos en contexto del portal central (sin tenant operativo).
     */
    public function isCentralPortal(): bool
    {
        return $this->resolved && $this->tenant === null;
    }

    /**
     * Limpia el contexto de tenant (útil para testing y queues).
     */
    public function forget(): void
    {
        $this->tenant = null;
        $this->resolved = false;

        // Restaurar conexión tenant al default inicial
        if (! empty($this->baseTenantConnection)) {
            foreach ($this->baseTenantConnection as $key => $value) {
                Config::set("database.connections.tenant.{$key}", $value);
            }
        }

        if ($this->baseDefaultConnection !== null) {
            Config::set('database.default', $this->baseDefaultConnection);
        }

        DB::purge('tenant');
    }

    /**
     * Configura dinámicamente la conexión 'tenant' para apuntar
     * a la base de datos del tenant resuelto.
     *
     * También establece 'tenant' como conexión por defecto para que
     * las llamadas a DB::table() y modelos sin conexión explícita
     * usen automáticamente la base del tenant activo.
     */
    protected function configureTenantConnection(Tenant $tenant): void
    {
        $overrides = config('tenancy.tenant_credentials.' . $tenant->slug, []);
        $base = $this->baseTenantConnection ?: config('database.connections.tenant', []);

        $databaseName = $overrides['database'] ?? $tenant->database ?? ($base['database'] ?? null);

        if ($databaseName) {
            Config::set('database.connections.tenant.database', $databaseName);
        }

        $host = $overrides['host'] ?? config('tenancy.tenant_db_host', $base['host'] ?? null);
        if ($host !== null) {
            Config::set('database.connections.tenant.host', $host);
        }

        $port = $overrides['port'] ?? config('tenancy.tenant_db_port', $base['port'] ?? null);
        if ($port !== null) {
            Config::set('database.connections.tenant.port', $port);
        }

        $username = $overrides['username'] ?? ($base['username'] ?? null);
        if ($username !== null) {
            Config::set('database.connections.tenant.username', $username);
        }

        if (array_key_exists('password', $overrides)) {
            Config::set('database.connections.tenant.password', $overrides['password']);
        } elseif (array_key_exists('password', $base)) {
            Config::set('database.connections.tenant.password', $base['password']);
        }

        // Purgar conexión existente para forzar reconexión
        DB::purge('tenant');

        // Reconectar con la nueva configuración
        DB::reconnect('tenant');

        // Establecer 'tenant' como conexión por defecto
        // para que DB::table() use la base del tenant activo
        Config::set('database.default', 'tenant');
    }
}
