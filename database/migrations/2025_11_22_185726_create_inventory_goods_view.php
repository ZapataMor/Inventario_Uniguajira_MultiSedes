<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS inventory_goods_view');

        DB::statement("
            CREATE VIEW inventory_goods_view AS
            SELECT
                i.id AS inventory_id,
                a.id AS asset_id,
                i.name AS inventory,
                a.name AS asset,
                a.image AS image,
                a.type AS type,
                CASE 
                    WHEN a.type = 'Cantidad' THEN COALESCE(SUM(aq.quantity), 0)
                    WHEN a.type = 'Serial' THEN COUNT(ae.id)
                    ELSE 0
                END AS quantity
            FROM assets a
            JOIN asset_inventory ai ON a.id = ai.asset_id
            JOIN inventories i ON ai.inventory_id = i.id
            LEFT JOIN asset_quantities aq ON ai.id = aq.asset_inventory_id AND a.type = 'Cantidad'
            LEFT JOIN asset_equipments ae ON ai.id = ae.asset_inventory_id AND a.type = 'Serial'
            GROUP BY
                i.id, a.id, i.name, a.name, a.image, a.type;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS inventory_goods_view;");
    }
};
