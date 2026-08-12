<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Labor documentada por una persona externa desde el formulario publico.
 */
class InventoryScheduleEntry extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'inventory_schedule_id',
        'work_name',
        'description',
        'responsible_name',
        'started_at',
        'finished_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InventorySchedule::class, 'inventory_schedule_id');
    }

    /**
     * Evidencias fotograficas, en el orden en que se adjuntaron.
     */
    public function images(): HasMany
    {
        return $this->hasMany(InventoryScheduleEntryImage::class, 'inventory_schedule_entry_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /**
     * Folio del comprobante: identifica la labor en el PDF descargable.
     *
     * Se deriva del codigo publico de la programacion, asi que dos
     * comprobantes nunca comparten folio dentro de la misma sede.
     */
    public function getReceiptCodeAttribute(): string
    {
        $code = strtoupper(substr((string) ($this->schedule?->code ?? 'SIN'), 0, 6));

        return sprintf('CMP-%s-%04d', $code, $this->id);
    }

    /**
     * Momento en que se recibio el formulario, en la hora de la sede.
     *
     * `created_at` se guarda en UTC, mientras que las fechas de inicio y
     * fin las escribe la persona externa en hora local. Mostrarlo sin
     * convertir haria parecer que la labor se registro horas despues de
     * haber terminado.
     */
    public function registeredAtLabel(string $timezone = 'America/Bogota'): string
    {
        return $this->created_at?->copy()->setTimezone($timezone)->format('d/m/Y H:i') ?? '—';
    }

    /**
     * Duracion legible de la labor (ej: "2 h 30 min").
     */
    public function getDurationLabelAttribute(): string
    {
        if (! $this->started_at || ! $this->finished_at) {
            return '—';
        }

        $minutes = $this->started_at->diffInMinutes($this->finished_at);

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? "{$hours} h" : "{$hours} h {$rest} min";
    }
}
