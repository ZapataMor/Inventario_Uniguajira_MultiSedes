<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenances', 'inventory_id')) {
                $afterColumn = Schema::hasColumn('maintenances', 'equipment_id')
                    ? 'equipment_id'
                    : (Schema::hasColumn('maintenances', 'asset_id') ? 'asset_id' : 'id');

                $table->foreignId('inventory_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('inventories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'inventory_id')) {
                $table->dropConstrainedForeignId('inventory_id');
            }
        });
    }
};
