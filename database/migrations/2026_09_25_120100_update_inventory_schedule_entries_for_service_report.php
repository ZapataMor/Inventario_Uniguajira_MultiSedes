<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El formulario publico pasa a ser el reporte del servicio (RA-F-33).
     *
     * Se piden el equipo intervenido (si aplica), la accion realizada, los
     * materiales y quien realizo la actividad. Desaparecen el nombre del
     * trabajo, el responsable y las observaciones; lo ya registrado no se
     * pierde: el responsable pasa a "realizada por" y el nombre del trabajo
     * junto con las observaciones pasan a la accion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('inventory_schedule_entries') || Schema::hasColumn('inventory_schedule_entries', 'action')) {
            return;
        }

        Schema::table('inventory_schedule_entries', function (Blueprint $table) {
            $table->boolean('is_equipment')->default(false)->after('inventory_schedule_id');
            $table->string('equipment_name')->nullable()->after('is_equipment');
            $table->string('equipment_model')->nullable()->after('equipment_name');
            $table->string('equipment_brand')->nullable()->after('equipment_model');
            $table->text('action')->nullable()->after('equipment_brand');
            $table->text('materials')->nullable()->after('action');
            $table->string('performed_by')->nullable()->after('materials');
        });

        DB::table('inventory_schedule_entries')
            ->chunkById(200, function ($entries) {
                foreach ($entries as $entry) {
                    $action = trim(implode("\n\n", array_filter([
                        $entry->work_name ?? null,
                        $entry->description ?? null,
                    ])));

                    DB::table('inventory_schedule_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'action' => $action !== '' ? $action : null,
                            'performed_by' => $entry->responsible_name ?? null,
                        ]);
                }
            });

        Schema::table('inventory_schedule_entries', function (Blueprint $table) {
            $table->dropColumn(['work_name', 'description', 'responsible_name']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventory_schedule_entries') || ! Schema::hasColumn('inventory_schedule_entries', 'action')) {
            return;
        }

        Schema::table('inventory_schedule_entries', function (Blueprint $table) {
            $table->string('work_name')->nullable()->after('inventory_schedule_id');
            $table->text('description')->nullable()->after('work_name');
            $table->string('responsible_name')->nullable()->after('description');
        });

        DB::table('inventory_schedule_entries')->update([
            'work_name' => DB::raw("COALESCE(LEFT(action, 255), '')"),
            'description' => DB::raw('materials'),
            'responsible_name' => DB::raw("COALESCE(performed_by, '')"),
        ]);

        Schema::table('inventory_schedule_entries', function (Blueprint $table) {
            $table->dropColumn([
                'is_equipment',
                'equipment_name',
                'equipment_model',
                'equipment_brand',
                'action',
                'materials',
                'performed_by',
            ]);
        });
    }
};
