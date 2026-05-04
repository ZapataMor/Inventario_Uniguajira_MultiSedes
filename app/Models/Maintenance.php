<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Maintenance extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = ['title', 'description', 'date', 'registered_by'];

    protected $casts = [
        'date' => 'date',
    ];

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function assetInventories()
    {
        return $this->belongsToMany(AssetInventory::class, 'asset_inventory_maintenance');
    }
}
