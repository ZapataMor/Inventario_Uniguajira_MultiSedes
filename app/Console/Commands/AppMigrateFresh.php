<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use PDO;
use Throwable;

/**
 * Ejecuta un reset completo de migraciones para central y tenants.
 */
class AppMigrateFresh extends Command
{
    protected $signature = 'app:migrate:fresh
        {--seed : Ejecutar seeders en central y tenants}
        {--force : Forzar ejecucion en produccion}
        {--ensure-databases : Crear las bases de datos faltantes antes de migrar}';

    protected $description = 'Resetea y migra base central + tenants activos en un solo comando';

    public function handle(): int
    {
        if ($this->option('ensure-databases')) {
            $ok = $this->ensureDatabasesExist();

            if (! $ok) {
                return self::FAILURE;
            }
        }

        $centralParams = [
            '--fresh' => true,
        ];

        if ($this->option('seed')) {
            $centralParams['--seed'] = true;
        }

        if ($this->option('force')) {
            $centralParams['--force'] = true;
        }

        $this->info('Ejecutando migraciones de base central...');
        $centralExit = Artisan::call('central:migrate', $centralParams, $this->output);

        if ($centralExit !== 0) {
            $this->error('Fallo central:migrate. Se detiene el proceso.');

            return self::FAILURE;
        }

        $tenantParams = [
            '--all' => true,
            '--fresh' => true,
        ];

        if ($this->option('seed')) {
            $tenantParams['--seed'] = true;
        }

        if ($this->option('force')) {
            $tenantParams['--force'] = true;
        }

        $this->info('Ejecutando migraciones de tenants...');
        $tenantExit = Artisan::call('tenant:migrate', $tenantParams, $this->output);

        if ($tenantExit !== 0) {
            $this->error('Fallo tenant:migrate. Revisa el log anterior.');

            return self::FAILURE;
        }

        $this->info('Proceso completado: central + tenants migrados correctamente.');

        return self::SUCCESS;
    }

    /**
     * Crea las bases de datos requeridas si no existen.
     */
    protected function ensureDatabasesExist(): bool
    {
        $host = (string) config('database.connections.tenant.host', env('DB_HOST', '127.0.0.1'));
        $port = (int) config('database.connections.tenant.port', env('DB_PORT', 3306));
        $username = (string) env('DB_USERNAME', 'root');
        $password = (string) env('DB_PASSWORD', '');

        $databases = $this->resolveDatabasesToEnsure();

        if (empty($databases)) {
            $this->warn('No se encontraron bases de datos para crear.');

            return true;
        }

        $this->info('Verificando/creando bases de datos faltantes...');

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
                $username,
                $password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            foreach ($databases as $database) {
                $quoted = '`' . str_replace('`', '``', $database) . '`';
                $sql = sprintf('CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $quoted);
                $pdo->exec($sql);
                $this->line(" - OK: {$database}");
            }

            return true;
        } catch (Throwable $e) {
            $this->error('No se pudieron crear/verificar las bases de datos.');
            $this->error($e->getMessage());

            return false;
        }
    }

    /**
     * Obtiene todas las bases de datos necesarias segun la configuracion.
     *
     * @return array<int, string>
     */
    protected function resolveDatabasesToEnsure(): array
    {
        $databases = [];

        $central = (string) env('DB_CENTRAL_DATABASE', '');
        if ($central !== '') {
            $databases[] = $central;
        }

        $defaultTenant = (string) env('DB_DATABASE', '');
        if ($defaultTenant !== '') {
            $databases[] = $defaultTenant;
        }

        $tenantCredentials = (array) config('tenancy.tenant_credentials', []);
        foreach ($tenantCredentials as $credentials) {
            if (! is_array($credentials)) {
                continue;
            }

            $database = (string) ($credentials['database'] ?? '');
            if ($database !== '') {
                $databases[] = $database;
            }
        }

        $databases = array_values(array_unique($databases));

        sort($databases);

        return $databases;
    }
}
