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
    /**
     * Super administrador principal: acceso total y unico que gestiona los niveles globales.
     */
    public const ROOT_ADMIN_EMAIL = 'recursosfisicos@uniguajira.edu.co';

    /**
     * Niveles globales (columna global_role): ambos entran a todas las sedes.
     */
    public const GLOBAL_ADMIN = 'super_administrador';

    public const GLOBAL_CONSULTOR = 'super_consultor';

    public const GLOBAL_LEVELS = [self::GLOBAL_ADMIN, self::GLOBAL_CONSULTOR];

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
     * Verifica si es el super administrador principal (recursosfisicos).
     */
    public function isRootAdmin(): bool
    {
        return mb_strtolower(trim((string) $this->email)) === self::ROOT_ADMIN_EMAIL;
    }

    /**
     * Verifica si el usuario tiene un nivel global (entra a todas las sedes).
     */
    public function isGlobalAdmin(): bool
    {
        $globalRoles = config('tenancy.global_roles', self::GLOBAL_LEVELS);

        return in_array($this->global_role, $globalRoles, true) || $this->isRootAdmin();
    }

    /**
     * Verifica si el usuario es super administrador (de cualquier nivel).
     */
    public function isSuperAdmin(): bool
    {
        return $this->isGlobalAdmin() || $this->role === 'super_administrador';
    }

    /**
     * Nivel global efectivo o null si es un usuario de sede. Los registros antiguos
     * con role = super_administrador sin global_role quedan como consultores.
     */
    public function globalLevel(): ?string
    {
        if ($this->isRootAdmin()) {
            return self::GLOBAL_ADMIN;
        }

        if (in_array($this->global_role, self::GLOBAL_LEVELS, true)) {
            return $this->global_role;
        }

        return $this->isSuperAdmin() ? self::GLOBAL_CONSULTOR : null;
    }

    /**
     * Super administrador con permisos de escritura en todas las sedes.
     */
    public function hasGlobalWriteAccess(): bool
    {
        return $this->globalLevel() === self::GLOBAL_ADMIN;
    }

    /**
     * Solo el super administrador principal otorga, cambia o edita niveles globales.
     */
    public function canManageGlobalLevels(): bool
    {
        return $this->isRootAdmin();
    }

    /**
     * Verifica si el usuario puede modificar datos en el contexto actual.
     * Un super administrador-administrador solo escribe dentro de una sede: el portal
     * central es una vista consolidada de lectura.
     */
    public function isAdministrator(): bool
    {
        if ($this->isSuperAdmin()) {
            return $this->hasGlobalWriteAccess() && tenant() !== null;
        }

        return $this->role === 'administrador';
    }

    /**
     * Verifica si el usuario es de solo lectura en el contexto actual.
     */
    public function isConsultor(): bool
    {
        return ! $this->isAdministrator();
    }

    /**
     * Obtiene el rol efectivo del usuario.
     */
    public function effectiveRole(): string
    {
        return $this->globalLevel() ?? (string) $this->role;
    }

    /**
     * Obtiene una etiqueta legible del rol efectivo.
     */
    public function displayRole(): string
    {
        if ($this->isRootAdmin()) {
            return 'Super Administrador principal';
        }

        return match ($this->effectiveRole()) {
            self::GLOBAL_ADMIN => 'Super Administrador - Administrador',
            self::GLOBAL_CONSULTOR => 'Super Administrador - Consultor',
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
            return $this->globalLevel();
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
