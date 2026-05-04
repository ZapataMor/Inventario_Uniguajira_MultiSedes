<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar asset_id a maintenances
        Schema::table('maintenances', function (Blueprint $table) {
            $table->foreignId('asset_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('assets')
                  ->nullOnDelete();
        });

        // 2. Eliminar tabla pivot (ya no se necesita)
        Schema::dropIfExists('asset_inventory_maintenance');
    }

    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });

        Schema::create('asset_inventory_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_inventory_id')->constrained('asset_inventory')->onDelete('cascade');
            $table->foreignId('maintenance_id')->constrained('maintenances')->onDelete('cascade');
            $table->timestamps();
        });
    }
};
