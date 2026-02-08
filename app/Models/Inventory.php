<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'responsible',
        'conservation_status',
        'group_id',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function assetInventories()
    {
        return $this->hasMany(AssetInventory::class);
    }

    public function items()
    {
        return $this->hasMany(Asset::class);
    }

    public function removedAssets()
    {
        return $this->hasMany(AssetRemoved::class);
    }

}
