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

    /**
     * Tipos de servicio del formato RA-F-33 ("Marque con una X").
     * "otros" no esta aqui: se guarda aparte, como texto libre.
     */
    public const SERVICE_TYPES = [
        'acometida_electrica' => 'Acometida eléctrica',
        'baterias_sanitarias' => 'Baterías sanitarias',
        'equipos_telefonicos' => 'Equipos telefónicos',
        'interruptores' => 'Interruptores',
        'paredes' => 'Paredes',
        'tableros' => 'Tableros',
        'ventiladores' => 'Ventiladores',
        'breaker' => 'Breaker',
        'aires_acondicionados' => 'Aires acondicionados',
        'lamparas' => 'Lámparas',
        'escritorios' => 'Escritorios',
        'toma_corriente' => 'Toma corriente',
        'puertas' => 'Puertas',
        'cortinas' => 'Cortinas',
        'mesas' => 'Mesas',
        'sillas' => 'Sillas',
    ];

    public const ACTIVITY_TYPES = [
        'mantenimiento' => 'Mantenimiento',
        'servicio_general' => 'Servicio en general',
    ];

    public const MAINTENANCE_TYPES = [
        'preventivo' => 'Preventivo',
        'correctivo' => 'Correctivo',
    ];

    protected $fillable = [
        'code',
        'title',
        'requester_name',
        'requester_position',
        'requester_dependency',
        'filing_number',
        'requested_at',
        'service_types',
        'service_other',
        'activity_type',
        'maintenance_type',
        'is_open',
        'created_by',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'service_types' => 'array',
        'requested_at' => 'date',
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
     * Nombre con el que se identifica el mantenimiento en tarjetas,
     * busquedas y comprobantes. Se arma con lo que se diligencio en la
     * solicitud, asi no hay que escribirlo a mano.
     *
     * @param  array<int, string>  $serviceLabels
     */
    public static function buildTitle(string $activityType, ?string $maintenanceType, array $serviceLabels): string
    {
        $activity = self::ACTIVITY_TYPES[$activityType] ?? 'Mantenimiento';
        $type = self::MAINTENANCE_TYPES[$maintenanceType ?? ''] ?? null;

        $title = $type ? $activity.' '.mb_strtolower($type) : $activity;

        if ($serviceLabels !== []) {
            $title .= ' · '.implode(', ', $serviceLabels);
        }

        return mb_strimwidth($title, 0, 255, '…');
    }

    /**
     * Servicios solicitados en texto legible, incluido "Otros: ...".
     *
     * @return array<int, string>
     */
    public function getServiceLabelsAttribute(): array
    {
        return self::serviceLabelsFor($this->service_types ?? [], $this->service_other);
    }

    /**
     * @param  array<int, string>  $types
     * @return array<int, string>
     */
    public static function serviceLabelsFor(array $types, ?string $other): array
    {
        $labels = collect($types)
            ->map(fn ($type) => self::SERVICE_TYPES[$type] ?? null)
            ->filter()
            ->values()
            ->all();

        if (filled($other)) {
            $labels[] = 'Otros: '.trim($other);
        }

        return $labels;
    }

    public function getActivityLabelAttribute(): ?string
    {
        return self::ACTIVITY_TYPES[$this->activity_type ?? ''] ?? null;
    }

    public function getMaintenanceTypeLabelAttribute(): ?string
    {
        return self::MAINTENANCE_TYPES[$this->maintenance_type ?? ''] ?? null;
    }

    /**
     * Bloque (grupo) al que pertenecen los salones de la programacion.
     */
    public function getBlockIdAttribute(): ?int
    {
        return $this->inventories->first()?->group_id;
    }

    /**
     * Valores con los que se precarga el modal de edicion.
     *
     * @return array<string, mixed>
     */
    public function formPayload(): array
    {
        return [
            'id' => $this->id,
            'requester_name' => $this->requester_name,
            'requester_position' => $this->requester_position,
            'requester_dependency' => $this->requester_dependency,
            'filing_number' => $this->filing_number,
            'requested_at' => $this->requested_at?->format('Y-m-d'),
            'service_types' => $this->service_types ?? [],
            'service_other' => $this->service_other,
            'activity_type' => $this->activity_type,
            'maintenance_type' => $this->maintenance_type,
            'group_id' => $this->block_id,
            'inventory_ids' => $this->inventories->pluck('id')->all(),
        ];
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
