<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
