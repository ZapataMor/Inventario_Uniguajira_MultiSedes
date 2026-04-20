<?php

namespace App\Providers;

use App\Support\Tenancy\TenantConnectionManager;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantConnectionManager::class, function () {
            return new TenantConnectionManager;
        });

        // TenantContext como singleton: una sola instancia por request
        $this->app->singleton(TenantContext::class, function ($app) {
            return new TenantContext($app->make(TenantConnectionManager::class));
        });
    }

    public function boot(): void
    {
        // Cargar migraciones centrales desde su directorio
        $this->loadMigrationsFrom(database_path('migrations/central'));
    }
}
