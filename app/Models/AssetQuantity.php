<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetQuantity extends Model
{
    use HasFactory;

    protected $fillable = ['asset_inventory_id', 'quantity'];

    public function assetInventory()
    {
        return $this->belongsTo(AssetInventory::class);
    }
}
