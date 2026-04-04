<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetInventory extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $table = 'asset_inventory';

    protected $fillable = ['asset_id', 'inventory_id'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function quantities()
    {
        return $this->hasMany(AssetQuantity::class);
    }

    public function equipments()
    {
        return $this->hasMany(AssetEquipment::class);
    }
}
