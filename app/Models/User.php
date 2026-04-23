<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    private const SUPER_ADMIN_EMAILS = [
        'recursosfisicos@uniguajira.edu.co',
    ];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'global_role',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function removedAssets()
    {
        return $this->hasMany(AssetRemoved::class);
    }

    // ─── Multi-Tenant Relationships ──────────────────────────────

    /**
     * Membresías del usuario en sedes.
     */
    public function tenantMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Central\UserTenant::class);
    }

    /**
     * Sedes a las que pertenece el usuario.
     */
    public function tenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Central\Tenant::class, 'user_tenant')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Verifica si el usuario es administrador general del sistema.
     */
    public function isGlobalAdmin(): bool
    {
        $globalRoles = config('tenancy.global_roles', ['super_administrador']);

        return in_array($this->global_role, $globalRoles)
            || in_array($this->email, self::SUPER_ADMIN_EMAILS, true);
    }

    /**
     * Verifica si el usuario es super administrador.
     */
    public function isSuperAdmin(): bool
    {
        return $this->isGlobalAdmin() || $this->role === 'super_administrador';
    }

    /**
     * Verifica si el usuario tiene privilegios administrativos.
     */
    public function isAdministrator(): bool
    {
        return ! $this->isSuperAdmin() && $this->role === 'administrador';
    }

    /**
     * Verifica si el usuario es consultor.
     */
    public function isConsultor(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role === 'consultor';
    }

    /**
     * Obtiene el rol efectivo del usuario.
     */
    public function effectiveRole(): string
    {
        return $this->isSuperAdmin() ? 'super_administrador' : $this->role;
    }

    /**
     * Obtiene una etiqueta legible del rol efectivo.
     */
    public function displayRole(): string
    {
        return match ($this->effectiveRole()) {
            'super_administrador' => 'Super Administrador',
            'administrador' => 'Administrador',
            default => 'Consultor',
        };
    }

    /**
     * Obtiene el rol del usuario en el tenant activo.
     */
    public function roleInTenant(?\App\Models\Central\Tenant $tenant = null): ?string
    {
        if ($this->isSuperAdmin()) {
            return 'super_administrador';
        }

        $tenant = $tenant ?? tenant();

        if (! $tenant) {
            return $this->role; // Fallback al rol actual
        }

        $membership = $this->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        return $membership?->role;
    }

    /**
     * Verifica si el usuario tiene acceso a un tenant específico.
     */
    public function hasAccessToTenant(\App\Models\Central\Tenant $tenant): bool
    {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        return $this->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();
    }
}
