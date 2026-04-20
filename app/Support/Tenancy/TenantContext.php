<?php

namespace App\Support\Tenancy;

use App\Models\Central\Tenant;

/**
 * Singleton que mantiene el estado del tenant activo durante el request.
 *
 * Se resuelve una sola vez al inicio del request por el middleware
 * ResolveTenant y queda disponible vía app(TenantContext::class)
 * o el helper tenant().
 */
class TenantContext
{
    public function __construct(
        protected TenantConnectionManager $connections,
    ) {}

    protected ?Tenant $tenant = null;

    protected bool $resolved = false;

    /**
     * Snapshot inicial de la conexion tenant y de la conexion por defecto.
     */
    protected ?array $baseSnapshot = null;

    /**
     * Establece el tenant activo y configura la conexión dinámica.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;

        if ($this->baseSnapshot === null) {
            $this->baseSnapshot = $this->connections->snapshot();
        }

        $this->connections->activate($tenant);
    }

    /**
     * Marca el request actual como portal central.
     */
    public function setCentral(): void
    {
        if ($this->baseSnapshot === null) {
            $this->baseSnapshot = $this->connections->snapshot();
        }

        $this->tenant = null;
        $this->resolved = true;

        $this->connections->useCentralConnectionAsDefault();
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

        if ($this->baseSnapshot !== null) {
            $this->connections->restore($this->baseSnapshot);
        }
    }
}
