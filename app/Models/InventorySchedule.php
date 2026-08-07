<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Programacion de un inventario o mantenimiento.
 *
 * El campo `code` es el identificador publico que viaja en el QR
 * y en el enlace del formulario externo.
 */
class InventorySchedule extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'code',
        'title',
        'is_open',
        'inventory_id',
        'created_by',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    // ─── Relaciones ──────────────────────────────────────────────

    public function entries(): HasMany
    {
        return $this->hasMany(InventoryScheduleEntry::class)->orderByDesc('started_at');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Genera un codigo publico corto que aun no exista en la sede activa.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::lower(Str::random(12));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Ubicacion legible: grupo e inventario asociados, si los hay.
     */
    public function getLocationLabelAttribute(): ?string
    {
        if (! $this->inventory) {
            return null;
        }

        $group = $this->inventory->group?->name;

        return $group
            ? "{$group} · {$this->inventory->name}"
            : $this->inventory->name;
    }

    /**
     * URL publica del formulario, incluyendo el slug de la sede
     * para que el tenant se resuelva sin depender de la sesion.
     */
    public function publicUrl(?string $tenantSlug = null): ?string
    {
        $slug = $tenantSlug ?? tenant('slug');

        if (! $slug) {
            return null;
        }

        return route('schedules.public.show', [
            'tenantSlug' => $slug,
            'code' => $this->code,
        ]);
    }
}
