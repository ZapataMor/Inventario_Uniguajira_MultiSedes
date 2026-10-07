<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActivityLog extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registrar una actividad
     */
    public static function record(string $action, string $description, array $data = []): self
    {
        $attributes = [
            'user_id'     => auth()->id(),
            'action'      => $action,
            'model'       => $data['model'] ?? null,
            'model_id'    => $data['model_id'] ?? null,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'old_values'  => $data['old_values'] ?? null,
            'new_values'  => $data['new_values'] ?? null,
        ];

        if (! tenant()) {
            return new self($attributes);
        }

        try {
            return self::create($attributes);
        } catch (Throwable $e) {
            Log::warning('No se pudo registrar la actividad.', [
                'action' => $action,
                'tenant_slug' => tenant('slug'),
                'error' => $e->getMessage(),
            ]);

            return new self($attributes);
        }
    }

    /**
     * Campos que cambiaron en un "update", listos para mostrar.
     * Soporta valores anidados (arrays), booleanos y nulos sin romper la vista.
     *
     * @return list<array{key: string, old: string, new: string}>
     */
    public function changedFields(int $limit = 18): array
    {
        $old = is_array($this->old_values) ? $this->old_values : [];
        $new = is_array($this->new_values) ? $this->new_values : [];
        $changes = [];

        foreach ($old as $key => $oldValue) {
            if (! isset($new[$key]) || $oldValue == $new[$key]) {
                continue;
            }

            $changes[] = [
                'key' => (string) $key,
                'old' => Str::limit(self::displayValue($oldValue), $limit),
                'new' => Str::limit(self::displayValue($new[$key]), $limit),
            ];
        }

        return $changes;
    }

    private static function displayValue(mixed $value): string
    {
        return match (true) {
            is_array($value) || is_object($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => '',
            default => (string) $value,
        };
    }

    /**
     * Obtener el ícono según la acción
     */
    public function getIconAttribute(): string
    {
        return match($this->action) {
            'login'         => 'fa-right-to-bracket',
            'logout'        => 'fa-right-from-bracket',
            'create'        => 'fa-plus-circle',
            'update'        => 'fa-pen-to-square',
            'delete'        => 'fa-trash',
            'restore'       => 'fa-trash-arrow-up',
            'view'          => 'fa-eye',
            'remove'        => 'fa-box-archive',
            'batch_create'  => 'fa-layer-group',
            default         => 'fa-circle-info',
        };
    }

    /**
     * Obtener el color según la acción
     */
    public function getColorAttribute(): string
    {
        return match($this->action) {
            'login'         => '#10b981', // green
            'logout'        => '#6b7280', // gray
            'create'        => '#3b82f6', // blue
            'update'        => '#f59e0b', // amber
            'delete'        => '#ef4444', // red
            'restore'       => '#8b5cf6', // purple
            'view'          => '#06b6d4', // cyan
            'remove'        => '#f97316', // orange
            'batch_create'  => '#0ea5e9', // sky
            default         => '#6b7280', // gray
        };
    }

    /**
     * Obtener el badge de color según la acción
     */
    public function getBadgeClassAttribute(): string
    {
        return match($this->action) {
            'login'         => 'badge-success',
            'logout'        => 'badge-secondary',
            'create'        => 'badge-primary',
            'update'        => 'badge-warning',
            'delete'        => 'badge-danger',
            'restore'       => 'badge-purple',
            'view'          => 'badge-info',
            'remove'        => 'badge-orange',
            'batch_create'  => 'badge-primary',
            default         => 'badge-secondary',
        };
    }

    public function getModelLabelAttribute()
    {
        return match ($this->model) {
            'AssetInventory' => 'Inventario',
            'AssetRemoved'   => 'Activos dados de baja',
            'User'           => 'Usuarios',
            'Role'           => 'Roles',
            default          => $this->model,
        };
    }
}
