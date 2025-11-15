<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetEquipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_inventory_id',
        'description',
        'brand',
        'model',
        'serial',
        'status',
        'color',
        'technical_conditions',
        'entry_date',
        'exit_date',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
    ];

    public function assetInventory()
    {
        return $this->belongsTo(AssetInventory::class);
    }
}
