<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central Database Connection
    |--------------------------------------------------------------------------
    |
    | The database connection used for central/shared data: tenants, domains,
    | branding, global users, memberships and roles.
    |
    */

    'central_connection' => env('TENANCY_CENTRAL_CONNECTION', 'central'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Prefix
    |--------------------------------------------------------------------------
    |
    | All tenant databases follow the naming convention:
    | {prefix}_{tenant_slug}  e.g. inventario_maicao
    |
    */

    'database_prefix' => env('TENANCY_DB_PREFIX', 'inventario_'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Connection Template
    |--------------------------------------------------------------------------
    |
    | The base connection config used as template for tenant connections.
    | The 'database' key will be overridden dynamically per tenant.
    |
    */

    'tenant_connection_template' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Default Tenant (Fallback)
    |--------------------------------------------------------------------------
    |
    | When no tenant can be resolved (e.g. in CLI commands), this slug
    | will be used as the default tenant. Set to null to require explicit
    | tenant resolution.
    |
    */

    'default_tenant' => env('TENANCY_DEFAULT_TENANT', 'maicao'),

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    |
    | The domain/subdomain used for the central portal where users can
    | select which sede to access. Leave null to disable portal access.
    |
    */

    'central_domain' => env('TENANCY_CENTRAL_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Resolution Strategy
    |--------------------------------------------------------------------------
    |
    | How the tenant is resolved from the incoming request:
    | - 'subdomain': maicao.inventario.uniguajira.edu.co
    | - 'domain': inventario-maicao.uniguajira.edu.co
    | - 'path': inventario.uniguajira.edu.co/maicao (not recommended)
    | - 'session': resolved from session after portal selection
    |
    */

    'resolution_strategy' => env('TENANCY_RESOLUTION', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | Base Domain
    |--------------------------------------------------------------------------
    |
    | The base domain used for subdomain resolution.
    | e.g. 'inventario.uniguajira.edu.co' → maicao.inventario.uniguajira.edu.co
    |
    */

    'base_domain' => env('TENANCY_BASE_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Storage
    |--------------------------------------------------------------------------
    |
    | Storage paths are isolated per tenant under this root.
    | e.g. storage/app/tenants/maicao/reports/
    |
    */

    'storage_root' => 'tenants',

    /*
    |--------------------------------------------------------------------------
    | Global Roles
    |--------------------------------------------------------------------------
    |
    | Roles that operate across all tenants.
    |
    */

    'global_roles' => [
        'super_administrador',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Roles
    |--------------------------------------------------------------------------
    |
    | Roles scoped to a single tenant.
    |
    */

    'tenant_roles' => [
        'administrador',
        'consultor',
    ],

];
