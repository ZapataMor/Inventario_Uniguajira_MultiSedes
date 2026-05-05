<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenances', 'equipment_id')) {
                $afterColumn = Schema::hasColumn('maintenances', 'asset_id') ? 'asset_id' : 'id';

                $table->unsignedBigInteger('equipment_id')->nullable()->after($afterColumn);
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'equipment_id')) {
                $table->dropColumn('equipment_id');
            }
        });
    }
};
