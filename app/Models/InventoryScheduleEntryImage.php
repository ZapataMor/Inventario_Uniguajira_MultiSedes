<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Evidencia fotografica de una labor documentada.
 *
 * El archivo se guarda en el storage de la sede y `path` conserva la
 * ruta relativa completa (incluye el prefijo `tenants/{slug}/`), asi
 * que servirlo no depende de que haya un tenant activo en el request.
 */
class InventoryScheduleEntryImage extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $table = 'inventory_schedule_entry_images';

    protected $fillable = [
        'inventory_schedule_entry_id',
        'path',
        'description',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(InventoryScheduleEntry::class, 'inventory_schedule_entry_id');
    }

    /**
     * Peso legible del archivo (ej: "412 KB").
     */
    public function getSizeLabelAttribute(): string
    {
        $bytes = (int) $this->size;

        if ($bytes <= 0) {
            return '—';
        }

        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
