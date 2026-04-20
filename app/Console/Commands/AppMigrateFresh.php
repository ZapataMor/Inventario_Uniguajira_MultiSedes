<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;
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
        $targets = $this->resolveConnectionTargets();

        if ($targets === []) {
            $this->warn('No se encontraron conexiones con base de datos configurada para verificar.');

            return true;
        }

        $this->info('Verificando/creando bases de datos faltantes...');

        foreach ($targets as $target) {
            try {
                if (! $this->databaseExists($target)) {
                    $this->createDatabase($target);
                }

                $this->line(" - OK: {$target['label']} -> {$target['database']}");
            } catch (Throwable $e) {
                $this->error(" - ERROR: {$target['label']} -> {$target['database']}");
                $this->error($e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{key: string, label: string, host: string, port: int, database: string, username: string, password: string}>
     */
    protected function resolveConnectionTargets(): array
    {
        $targets = [];

        $central = $this->makeConnectionTarget(
            'central',
            'central',
            (array) config('database.connections.central', [])
        );

        if ($central !== null) {
            $targets[] = $central;
        }

        foreach ($this->resolveTenantTargets() as $target) {
            $targets[] = $target;
        }

        $uniqueTargets = [];

        foreach ($targets as $target) {
            $uniqueTargets[$target['key']] = $target;
        }

        return array_values($uniqueTargets);
    }

    /**
     * @return array<int, array{key: string, label: string, host: string, port: int, database: string, username: string, password: string}>
     */
    protected function resolveTenantTargets(): array
    {
        $fromCentral = $this->resolveTenantTargetsFromCentralDatabase();

        if ($fromCentral !== []) {
            return $fromCentral;
        }

        return $this->resolveTenantTargetsFromLegacyConfig();
    }

    /**
     * @return array<int, array{key: string, label: string, host: string, port: int, database: string, username: string, password: string}>
     */
    protected function resolveTenantTargetsFromCentralDatabase(): array
    {
        try {
            if (! Schema::connection('central')->hasTable('tenants')) {
                return [];
            }
        } catch (Throwable $e) {
            return [];
        }

        $baseTenantConnection = (array) config('database.connections.tenant', []);

        return Tenant::query()
            ->orderBy('id')
            ->get()
            ->map(function (Tenant $tenant) use ($baseTenantConnection): ?array {
                return $this->makeConnectionTarget(
                    "tenant:{$tenant->slug}",
                    "tenant {$tenant->slug}",
                    array_replace($baseTenantConnection, $tenant->tenantConnectionAttributes($baseTenantConnection))
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string, host: string, port: int, database: string, username: string, password: string}>
     */
    protected function resolveTenantTargetsFromLegacyConfig(): array
    {
        $targets = [];
        $baseTenantConnection = (array) config('database.connections.tenant', []);
        $tenantCredentials = (array) config('tenancy.tenant_credentials', []);

        foreach ($tenantCredentials as $slug => $credentials) {
            if (! is_array($credentials)) {
                continue;
            }

            $target = $this->makeConnectionTarget(
                "tenant:{$slug}",
                "tenant {$slug}",
                array_replace($baseTenantConnection, $credentials)
            );

            if ($target !== null) {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /**
     * @param  array<string, mixed>  $connection
     * @return array{key: string, label: string, host: string, port: int, database: string, username: string, password: string}|null
     */
    protected function makeConnectionTarget(string $key, string $label, array $connection): ?array
    {
        $database = $this->normalizeString($connection['database'] ?? null);

        if ($database === null) {
            return null;
        }

        $host = $this->normalizeString($connection['host'] ?? null) ?? '127.0.0.1';
        $port = (int) ($connection['port'] ?? 3306);
        $username = $this->normalizeString($connection['username'] ?? null);

        if ($username === null) {
            throw new RuntimeException("Falta username para la conexion {$label}.");
        }

        $password = array_key_exists('password', $connection)
            ? (string) ($connection['password'] ?? '')
            : '';

        return [
            'key' => $key,
            'label' => $label,
            'host' => $host,
            'port' => $port > 0 ? $port : 3306,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $target
     */
    protected function databaseExists(array $target): bool
    {
        try {
            new PDO(
                $this->databaseDsn($target),
                $target['username'],
                $target['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            return true;
        } catch (Throwable $e) {
            if ($this->causedByMissingDatabase($e)) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $target
     */
    protected function createDatabase(array $target): void
    {
        $pdo = new PDO(
            $this->serverDsn($target),
            $target['username'],
            $target['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $quotedDatabase = '`'.str_replace('`', '``', $target['database']).'`';
        $sql = sprintf(
            'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $quotedDatabase
        );

        $pdo->exec($sql);
    }

    /**
     * @param  array{host: string, port: int, database: string}  $target
     */
    protected function databaseDsn(array $target): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $target['host'],
            $target['port'],
            $target['database'],
        );
    }

    /**
     * @param  array{host: string, port: int}  $target
     */
    protected function serverDsn(array $target): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $target['host'],
            $target['port'],
        );
    }

    protected function causedByMissingDatabase(Throwable $e): bool
    {
        $message = mb_strtolower($e->getMessage());

        return str_contains($message, 'unknown database')
            || str_contains($message, '1049');
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
