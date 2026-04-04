<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetEquipmentRemoved extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $table = 'asset_equipments_removed';

    protected $fillable = [
        'name',
        'image',
        'description',
        'brand',
        'model',
        'serial',
        'status',
        'color',
        'technical_conditions',
        'entry_date',
        'exit_date',
        'reason',
        'asset_id',
        'inventory_id',
        'equipment_id',
        'user_id'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
    ];

    /**
     * Relación con el bien original
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Relación con el inventario
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Relación con el usuario que dio de baja
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}