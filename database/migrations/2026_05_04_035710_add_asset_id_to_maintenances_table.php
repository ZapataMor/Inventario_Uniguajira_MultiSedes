<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (! Schema::hasColumn('maintenances', 'asset_id')) {
                $table->foreignId('asset_id')
                    ->after('id')
                    ->constrained('assets')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            if (Schema::hasColumn('maintenances', 'asset_id')) {
                $table->dropConstrainedForeignId('asset_id');
            }
        });
    }
};
