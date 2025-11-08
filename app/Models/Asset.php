<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'image'];

    public function assetInventories()
    {
        return $this->hasMany(AssetInventory::class);
    }
}
