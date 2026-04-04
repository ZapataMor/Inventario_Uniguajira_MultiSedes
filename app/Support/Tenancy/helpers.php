<?php

use App\Models\Central\Tenant;
use App\Support\Tenancy\TenantContext;

if (! function_exists('tenant')) {
    /**
     * Obtiene el tenant activo o una propiedad específica del tenant.
     *
     * tenant()          → Tenant model
     * tenant('slug')    → 'maicao'
     * tenant('name')    → 'Sede Maicao'
     */
    function tenant(?string $attribute = null): mixed
    {
        $context = app(TenantContext::class);
        $tenant = $context->get();

        if ($attribute !== null && $tenant) {
            return $tenant->getAttribute($attribute);
        }

        return $tenant;
    }
}

if (! function_exists('tenant_asset')) {
    /**
     * Genera una ruta de asset relativa al tenant activo.
     *
     * tenant_asset('reports/reporte.pdf') → 'tenants/maicao/reports/reporte.pdf'
     */
    function tenant_asset(string $path): string
    {
        $context = app(TenantContext::class);
        $slug = $context->slug() ?? 'default';
        $root = config('tenancy.storage_root', 'tenants');

        return "{$root}/{$slug}/{$path}";
    }
}

if (! function_exists('tenant_storage_path')) {
    /**
     * Genera la ruta absoluta de storage para el tenant activo.
     */
    function tenant_storage_path(string $path = ''): string
    {
        return storage_path('app/' . tenant_asset($path));
    }
}
