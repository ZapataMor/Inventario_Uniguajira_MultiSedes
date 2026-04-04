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
     * Establece el tenant activo y configura la conexión dinámica.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;

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

        // Restaurar conexión tenant al default
        $defaultDb = config('database.connections.tenant.database');
        Config::set('database.connections.tenant.database', $defaultDb);
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
        $databaseName = $tenant->database;

        Config::set('database.connections.tenant.database', $databaseName);

        // Purgar conexión existente para forzar reconexión
        DB::purge('tenant');

        // Reconectar con la nueva configuración
        DB::reconnect('tenant');

        // Establecer 'tenant' como conexión por defecto
        // para que DB::table() use la base del tenant activo
        Config::set('database.default', 'tenant');
    }
}
