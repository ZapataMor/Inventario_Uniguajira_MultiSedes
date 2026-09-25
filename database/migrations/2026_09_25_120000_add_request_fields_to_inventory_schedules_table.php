<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la solicitud de servicio (formato RA-F-33).
     *
     * Al crear un mantenimiento, el personal de la sede diligencia la
     * informacion del solicitante, los tipos de servicio requeridos y la
     * descripcion de la actividad. Las columnas son nullable porque las
     * programaciones anteriores a este formato no tienen esos datos.
     */
    public function up(): void
    {
        if (! Schema::hasTable('inventory_schedules') || Schema::hasColumn('inventory_schedules', 'requester_name')) {
            return;
        }

        Schema::table('inventory_schedules', function (Blueprint $table) {
            // Informacion del solicitante
            $table->string('requester_name')->nullable()->after('title');
            $table->string('requester_position')->nullable()->after('requester_name');
            $table->string('requester_dependency')->nullable()->after('requester_position');
            $table->string('filing_number', 60)->nullable()->after('requester_dependency');
            $table->date('requested_at')->nullable()->after('filing_number');

            // Tipo de servicio solicitado (seleccion multiple + "otros")
            $table->json('service_types')->nullable()->after('requested_at');
            $table->string('service_other')->nullable()->after('service_types');

            // Descripcion de la actividad
            $table->string('activity_type', 30)->nullable()->after('service_other');
            $table->string('maintenance_type', 20)->nullable()->after('activity_type');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_schedules') || ! Schema::hasColumn('inventory_schedules', 'requester_name')) {
            return;
        }

        Schema::table('inventory_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'requester_name',
                'requester_position',
                'requester_dependency',
                'filing_number',
                'requested_at',
                'service_types',
                'service_other',
                'activity_type',
                'maintenance_type',
            ]);
        });
    }
};
