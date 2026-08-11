<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asegura la columna `equipment_id` en `maintenances`.
     *
     * La migracion 2026_05_04_180000 figura como ejecutada en las bases de
     * sede, pero la columna no quedo creada: hacia el `hasColumn` dentro del
     * closure de `Schema::table`, asi que el ALTER se armaba en vacio. Sin esa
     * columna, el mantenimiento por serial individual y el masivo no funcionan.
     *
     * Aqui la comprobacion va fuera del closure para que el chequeo ocurra
     * contra la conexion ya resuelta y el ALTER se emita de verdad.
     */
    public function up(): void
    {
        if (! Schema::hasTable('maintenances') || Schema::hasColumn('maintenances', 'equipment_id')) {
            return;
        }

        $after = Schema::hasColumn('maintenances', 'asset_id') ? 'asset_id' : 'id';

        Schema::table('maintenances', function (Blueprint $table) use ($after) {
            $table->unsignedBigInteger('equipment_id')->nullable()->after($after);
            // Unico patron de consulta de la columna: historial por serial.
            $table->index('equipment_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('maintenances') || ! Schema::hasColumn('maintenances', 'equipment_id')) {
            return;
        }

        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropIndex(['equipment_id']);
            $table->dropColumn('equipment_id');
        });
    }
};
