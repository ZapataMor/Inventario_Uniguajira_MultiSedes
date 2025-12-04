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
                COALESCE(SUM(aq.quantity), COUNT(ae.id)) AS quantity
            FROM assets a
            JOIN asset_inventory ai ON a.id = ai.asset_id
            JOIN inventories i ON ai.inventory_id = i.id
            LEFT JOIN asset_quantities aq ON ai.id = aq.asset_inventory_id
            LEFT JOIN asset_equipments ae ON ai.id = ae.asset_inventory_id
            GROUP BY
                i.id, a.id, i.name, a.name, a.image, a.type
            HAVING quantity > 0;
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
