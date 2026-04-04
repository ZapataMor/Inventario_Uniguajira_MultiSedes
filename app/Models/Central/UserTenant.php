<?php

namespace App\Models\Central;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membresía usuario-sede.
 *
 * Define qué usuarios tienen acceso a qué sedes y con qué rol.
 * Un usuario puede tener membresía en múltiples sedes.
 */
class UserTenant extends Model
{
    protected $connection = 'central';

    protected $table = 'user_tenant';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
