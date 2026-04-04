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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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
        return $this->attributes['database']
            ?? config('tenancy.database_prefix') . $this->slug;
    }
}
