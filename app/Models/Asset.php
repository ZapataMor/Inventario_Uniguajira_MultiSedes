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
}
