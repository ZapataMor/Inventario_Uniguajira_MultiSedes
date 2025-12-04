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
        DB::statement('DROP VIEW IF EXISTS assets_summary_view');
        
        DB::statement("
            CREATE VIEW assets_summary_view AS
            SELECT
                a.id AS id,
                a.name AS name,
                a.type AS type,
                a.image AS image,
                (
                    COALESCE(SUM(aq.quantity), 0) +
                    COALESCE(COUNT(ae.id), 0)
                ) AS total_quantity
            FROM assets a
            LEFT JOIN asset_inventory ai ON a.id = ai.asset_id
            LEFT JOIN asset_quantities aq ON ai.id = aq.asset_inventory_id
            LEFT JOIN asset_equipments ae ON ai.id = ae.asset_inventory_id
            GROUP BY
                a.id, a.name, a.type, a.image
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS assets_summary_view");
    }
};
