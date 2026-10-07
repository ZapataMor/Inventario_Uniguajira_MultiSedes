<?php

namespace App\Models;

use App\Concerns\UsesTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inventory extends Model
{
    use HasFactory, UsesTenantConnection;

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

    /**
     * Agrega total_asset_types (bienes distintos) y total_assets
     * (suma de cantidades + numero de seriales) a cada inventario.
     */
    public function scopeWithAssetTotals($query)
    {
        return $query
            ->select('inventories.*')

            // === COUNT OF DISTINCT ASSETS ===
            ->selectRaw("
                (
                    SELECT COUNT(DISTINCT a.id)
                    FROM asset_inventory ai
                    LEFT JOIN assets a ON ai.asset_id = a.id
                    WHERE ai.inventory_id = inventories.id
                ) AS total_asset_types
            ")

            // === TOTAL AMOUNT = SUM(quantity) + COUNT(serials) ===
            ->selectRaw("
                (
                    SELECT
                        COALESCE((
                            SELECT SUM(aq.quantity)
                            FROM asset_quantities aq
                            WHERE aq.asset_inventory_id IN (
                                SELECT ai2.id
                                FROM asset_inventory ai2
                                WHERE ai2.inventory_id = inventories.id
                            )
                        ), 0)
                        +
                        COALESCE((
                            SELECT COUNT(ae.id)
                            FROM asset_equipments ae
                            WHERE ae.asset_inventory_id IN (
                                SELECT ai3.id
                                FROM asset_inventory ai3
                                WHERE ai3.inventory_id = inventories.id
                            )
                        ), 0)
                ) AS total_assets
            ");
    }

}
