<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Ejecuta migraciones sobre las bases de datos de los tenants.
 *
 * Permite ejecutar migraciones en un tenant especifico o en todos.
 * Las migraciones de tenant estan en database/migrations.
 */
class TenantMigrate extends Command
{
    protected $signature = 'tenant:migrate
        {--tenant= : Slug del tenant especifico (ej: maicao)}
        {--all : Ejecutar en todos los tenants activos}
        {--fresh : Hacer migrate:fresh en lugar de migrate}
        {--seed : Ejecutar seeders despues de migrar}
        {--class= : Seeder especifico a ejecutar cuando se usa --seed}
        {--force : Forzar ejecucion en produccion}';

    protected $description = 'Ejecuta migraciones en las bases de datos de los tenants';

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');
        $all = $this->option('all');

        if (! $tenantSlug && ! $all) {
            $this->error('Debes especificar --tenant=slug o --all');

            return self::FAILURE;
        }

        $tenants = $all
            ? Tenant::where('is_active', true)->get()
            : Tenant::where('slug', $tenantSlug)->get();

        if ($tenants->isEmpty()) {
            $this->error('No se encontraron tenants.');

            return self::FAILURE;
        }

        $context = app(TenantContext::class);

        foreach ($tenants as $tenant) {
            $this->info("Migrando tenant: {$tenant->name} ({$tenant->database})");

            $context->set($tenant);

            $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
            $params = [
                '--database' => 'tenant',
                '--path' => 'database/migrations',
                '--realpath' => false,
            ];

            if ($this->option('force')) {
                $params['--force'] = true;
            }

            if ($this->option('seed') && $this->option('fresh')) {
                $params['--seed'] = true;
            }

            $exitCode = Artisan::call($command, $params, $this->output);

            if ($exitCode !== 0) {
                $this->error("Error migrando {$tenant->name}");
                $context->forget();

                return self::FAILURE;
            }

            if ($this->option('seed') && ! $this->option('fresh')) {
                $seedParams = [
                    '--database' => 'tenant',
                    '--class' => $this->option('class') ?: 'Database\\Seeders\\DatabaseSeeder',
                ];

                if ($this->option('force')) {
                    $seedParams['--force'] = true;
                }

                $seedExitCode = Artisan::call('db:seed', $seedParams, $this->output);

                if ($seedExitCode !== 0) {
                    $this->error("Error ejecutando seeders en {$tenant->name}");
                    $context->forget();

                    return self::FAILURE;
                }
            }

            $this->info("Tenant {$tenant->name} migrado correctamente.");
            $context->forget();
        }

        $this->info('Migracion de tenants completada.');

        return self::SUCCESS;
    }
}
