<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTenants extends Command
{
    protected $signature   = 'migrate:tenants';
    protected $description = 'Corre las migraciones pendientes en todas las bases de datos tenant';

    public function handle(): void
    {
        $tenants = DB::connection('central')
            ->table('tenants')
            ->where('is_active', true)
            ->select('name', 'database')
            ->get();

        if ($tenants->isEmpty()) {
            $this->warn('No se encontraron tenants activos.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("── {$tenant->name} ({$tenant->database})");

            config(['database.connections.tenant_migrate' => array_merge(
                config('database.connections.mysql'),
                ['database' => $tenant->database]
            )]);

            DB::purge('tenant_migrate');

            $migrator = app('migrator');
            $migrator->setConnection('tenant_migrate');
            $repo = $migrator->getRepository();

            if (! $repo->repositoryExists()) {
                $repo->createRepository();
            }

            // Solo archivos del root de migrations, excluye la subcarpeta central/
            $allFiles    = $migrator->getMigrationFiles([database_path('migrations')]);
            $tenantFiles = array_filter($allFiles, fn ($path) =>
                ! str_contains(str_replace('\\', '/', $path), '/central/')
            );

            $ran = $repo->getRan();

            // Si la BD ya existe (tiene tabla users) pero las migraciones no están
            // registradas, las marcamos como ejecutadas para no intentar recrearlas.
            if (DB::connection('tenant_migrate')->getSchemaBuilder()->hasTable('users')) {
                $untracked = array_diff(array_keys($tenantFiles), $ran);
                if (! empty($untracked)) {
                    $batch = ($repo->getLastBatchNumber() ?: 0) + 1;
                    foreach ($untracked as $name) {
                        $repo->log($name, $batch);
                    }
                    $ran = $repo->getRan();
                    $this->line('   <fg=gray>Base de datos existente — migraciones históricas marcadas como ejecutadas.</>');
                }
            }

            $pending = array_diff_key($tenantFiles, array_flip($ran));

            if (empty($pending)) {
                $this->line('   <fg=gray>Nothing to migrate.</>');
                continue;
            }

            $migrator->runPending($tenantFiles);

            foreach ($migrator->getNotes() as $note) {
                $this->line("   $note");
            }
        }

        $this->newLine();
        $this->info('✓ Migraciones completadas en todos los tenants.');
    }
}
