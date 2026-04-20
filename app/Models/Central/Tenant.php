<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Representa una sede/tenant del sistema.
 *
 * Cada tenant tiene su propia base de datos operativa
 * y su configuración de branding independiente.
 */
class Tenant extends Model
{
    protected $connection = 'central';

    protected $table = 'tenants';

    protected $fillable = [
        'name',
        'slug',
        'database',
        'host',
        'port',
        'username',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function branding(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TenantBranding::class);
    }

    public function userTenants(): HasMany
    {
        return $this->hasMany(UserTenant::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Devuelve la URL principal de este tenant (primer dominio activo).
     */
    public function primaryDomain(): ?string
    {
        return $this->domains()
            ->where('is_active', true)
            ->where('is_primary', true)
            ->value('domain');
    }

    /**
     * Nombre completo de la base de datos operativa de este tenant.
     */
    public function getDatabaseAttribute(): string
    {
        $slug = (string) ($this->attributes['slug'] ?? $this->slug ?? '');
        $override = $slug !== ''
            ? config("tenancy.tenant_credentials.{$slug}.database")
            : null;

        if (is_string($override) && $override !== '') {
            return $override;
        }

        $database = $this->attributes['database'] ?? null;

        if (is_string($database) && $database !== '') {
            return $database;
        }

        return config('tenancy.database_prefix').$slug;
    }

    /**
     * Configuracion efectiva de conexion para este tenant.
     *
     * Prioridad:
     * 1. columnas del tenant en la BD central
     * 2. overrides legacy por slug en config/tenancy.php
     * 3. plantilla base de la conexion tenant
     *
     * @return array<string, mixed>
     */
    public function tenantConnectionAttributes(array $baseConnection = []): array
    {
        $legacy = $this->legacyConnectionOverrides();
        $password = $this->getAttribute('password');

        return [
            'host' => $this->firstNonEmptyValue(
                $this->attributes['host'] ?? null,
                $legacy['host'] ?? null,
                config('tenancy.tenant_db_host'),
                $baseConnection['host'] ?? null,
            ),
            'port' => $this->firstNonEmptyValue(
                $this->attributes['port'] ?? null,
                $legacy['port'] ?? null,
                config('tenancy.tenant_db_port'),
                $baseConnection['port'] ?? null,
            ),
            'database' => $this->database,
            'username' => $this->firstNonEmptyValue(
                $this->attributes['username'] ?? null,
                $legacy['username'] ?? null,
                config('tenancy.tenant_db_username'),
                $baseConnection['username'] ?? null,
            ),
            'password' => $password !== null
                ? $password
                : $this->legacyPasswordOrFallback($legacy, $baseConnection),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function legacyConnectionOverrides(): array
    {
        $slug = (string) ($this->attributes['slug'] ?? $this->slug ?? '');

        if ($slug === '') {
            return [];
        }

        $legacy = (array) config("tenancy.tenant_credentials.{$slug}", []);

        return array_filter(
            $legacy,
            static fn ($value): bool => $value !== null && $value !== ''
        );
    }

    protected function legacyPasswordOrFallback(array $legacy, array $baseConnection): mixed
    {
        if (array_key_exists('password', $legacy)) {
            return $legacy['password'];
        }

        if (config('tenancy.tenant_db_password') !== null) {
            return config('tenancy.tenant_db_password');
        }

        return $baseConnection['password'] ?? null;
    }

    protected function firstNonEmptyValue(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if (is_string($value)) {
                if (trim($value) !== '') {
                    return $value;
                }

                continue;
            }

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
