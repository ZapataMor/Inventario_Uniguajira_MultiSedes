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
        DB::statement('DROP VIEW IF EXISTS serial_goods_view');

        DB::statement("
            CREATE VIEW serial_goods_view AS
            SELECT
                i.id AS inventory_id,
                b.id AS asset_id,
                i.name AS inventory,
                b.name AS asset,
                b.image AS image,
                ai.id AS asset_inventory_id,
                ae.id AS asset_equipment_id,
                ae.description,
                ae.brand,
                ae.model,
                ae.serial,
                ae.status,
                ae.color,
                ae.technical_conditions,
                ae.entry_date,
                ae.exit_date
            FROM assets b
            JOIN asset_inventory ai ON b.id = ai.asset_id
            JOIN inventories i ON ai.inventory_id = i.id
            JOIN asset_equipments ae ON ai.id = ae.asset_inventory_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS serial_goods_view");
    }
};
