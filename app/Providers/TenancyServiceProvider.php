<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TenantContext como singleton: una sola instancia por request
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });
    }

    public function boot(): void
    {
        // Cargar migraciones centrales desde su directorio
        $this->loadMigrationsFrom(database_path('migrations/central'));
    }
}
