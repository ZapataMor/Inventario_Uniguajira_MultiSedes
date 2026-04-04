<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Ejecuta migraciones sobre la base de datos central.
 *
 * Las migraciones centrales están en database/migrations/central.
 */
class CentralMigrate extends Command
{
    protected $signature = 'central:migrate
        {--fresh : Hacer migrate:fresh en lugar de migrate}
        {--seed : Ejecutar seeders después de migrar}
        {--force : Forzar ejecución en producción}';

    protected $description = 'Ejecuta migraciones en la base de datos central';

    public function handle(): int
    {
        $this->info('Migrando base de datos central...');

        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';

        $params = [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
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
            $this->error('Error migrando la base central.');
            return self::FAILURE;
        }

        if ($this->option('seed') && ! $this->option('fresh')) {
            $seedParams = [
                '--database' => 'central',
            ];

            if ($this->option('force')) {
                $seedParams['--force'] = true;
            }

            $seedExitCode = Artisan::call('db:seed', $seedParams, $this->output);

            if ($seedExitCode !== 0) {
                $this->error('Error ejecutando seeders en la base central.');
                return self::FAILURE;
            }
        }

        $this->info('Base de datos central migrada correctamente.');
        return self::SUCCESS;
    }
}
