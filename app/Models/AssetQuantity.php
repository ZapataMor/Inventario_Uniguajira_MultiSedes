<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetQuantity extends Model
{
    use HasFactory;

    protected $fillable = ['asset_inventory_id', 'quantity'];

    public function assetInventory()
    {
        return $this->belongsTo(AssetInventory::class);
    }
}
