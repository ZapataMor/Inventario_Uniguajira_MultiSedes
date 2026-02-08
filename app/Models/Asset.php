<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'image'];

    public function assetInventories()
    {
        return $this->hasMany(AssetInventory::class);
    }

    public function quantities()
    {
        return $this->hasMany(AssetQuantity::class, 'asset_inventory_id');
    }

    public function equipments()
    {
        return $this->hasMany(AssetEquipment::class, 'asset_inventory_id');
    }

    public function removedRecords()
    {
        return $this->hasMany(AssetRemoved::class);
    }

}
