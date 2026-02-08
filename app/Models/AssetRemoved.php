<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetRemoved extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'type', 
        'image', 
        'quantity', 
        'reason',
        'asset_id',
        'inventory_id',
        'user_id'
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}