<?php

namespace App\Support\Tenancy;

use App\Models\Central\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantConnectionManager
{
    /**
     * Plantilla base e inmutable de la conexion tenant.
     *
     * @var array<string, mixed>
     */
    protected array $baseTenantTemplate;

    public function __construct()
    {
        $this->baseTenantTemplate = config('database.connections.tenant', []);
    }

    /**
     * Snapshot actual de la conexion tenant y de la conexion por defecto.
     *
     * @return array{tenant_connection: array<string, mixed>, default_connection: string|null}
     */
    public function snapshot(): array
    {
        return [
            'tenant_connection' => config('database.connections.tenant', []),
            'default_connection' => config('database.default'),
        ];
    }

    /**
     * Activa la conexion tenant con la configuracion efectiva de la sede.
     *
     * @return array<string, mixed>
     */
    public function activate(Tenant $tenant): array
    {
        $baseConnection = $this->baseTenantTemplate;
        $connection = array_replace($baseConnection, $tenant->tenantConnectionAttributes($baseConnection));

        Config::set('database.connections.tenant', $connection);

        DB::purge('tenant');
        DB::reconnect('tenant');

        Config::set('database.default', 'tenant');

        return $connection;
    }

    /**
     * Restaura la configuracion previa de la conexion tenant.
     *
     * @param  array{tenant_connection: array<string, mixed>, default_connection: string|null}  $snapshot
     */
    public function restore(array $snapshot): void
    {
        Config::set('database.connections.tenant', $snapshot['tenant_connection']);
        Config::set(
            'database.default',
            $snapshot['default_connection'] ?: config('tenancy.central_connection', 'central')
        );

        DB::purge('tenant');

        if ($this->canReconnect($snapshot['tenant_connection'])) {
            DB::reconnect('tenant');
        }
    }

    /**
     * Vuelve al contexto central cuando no hay tenant activo.
     */
    public function useCentralConnectionAsDefault(): void
    {
        Config::set('database.default', config('tenancy.central_connection', 'central'));
        DB::purge('tenant');
    }

    /**
     * Ejecuta un callback con la conexion tenant activada temporalmente.
     */
    public function runForTenant(Tenant $tenant, callable $callback): mixed
    {
        $snapshot = $this->snapshot();

        $this->activate($tenant);

        try {
            return $callback($tenant);
        } finally {
            $this->restore($snapshot);
        }
    }

    protected function canReconnect(array $connection): bool
    {
        $database = $connection['database'] ?? null;

        return is_string($database) && trim($database) !== '';
    }
}
