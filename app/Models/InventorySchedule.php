<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * Programacion de un inventario o mantenimiento.
 *
 * El campo `code` es el identificador publico que viaja en el QR
 * y en el enlace del formulario externo.
 *
 * Cada programacion es de un solo uso: se documenta una vez desde el
 * formulario publico y a partir de ahi el QR queda consumido. Para una
 * nueva labor se crea otra programacion, con su propio QR.
 */
class InventorySchedule extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'code',
        'title',
        'is_open',
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

    /**
     * Labor documentada. Al ser de un solo uso, es la unica que existe.
     */
    public function entry(): HasOne
    {
        return $this->hasOne(InventoryScheduleEntry::class)->latestOfMany();
    }

    /**
     * Ubicaciones donde debe realizarse la labor.
     */
    public function inventories(): BelongsToMany
    {
        return $this->belongsToMany(Inventory::class, 'inventory_schedule_inventory')
            ->withTimestamps()
            ->orderBy('name');
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
     * Una programacion ya diligenciada no vuelve a recibir registros:
     * su QR se considera consumido.
     */
    public function isCompleted(): bool
    {
        return $this->relationLoaded('entry')
            ? $this->entry !== null
            : $this->entries()->exists();
    }

    /**
     * El QR y el enlace solo se muestran mientras la programacion
     * siga pendiente de diligenciar.
     */
    public function isShareable(): bool
    {
        return ! $this->isCompleted();
    }

    /**
     * Ubicaciones legibles: "Grupo · Inventario" por cada una.
     *
     * @return array<int, string>
     */
    public function getLocationLabelsAttribute(): array
    {
        return $this->inventories
            ->map(function (Inventory $inventory) {
                $group = $inventory->group?->name;

                return $group
                    ? "{$group} · {$inventory->name}"
                    : $inventory->name;
            })
            ->values()
            ->all();
    }

    /**
     * Todas las ubicaciones en una sola linea.
     */
    public function getLocationLabelAttribute(): ?string
    {
        $labels = $this->location_labels;

        return $labels === [] ? null : implode(', ', $labels);
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
