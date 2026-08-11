<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una programacion puede realizarse en varias ubicaciones.
     *
     * Sustituye la columna `inventory_id` (una sola ubicacion) por una
     * tabla pivote. Los datos existentes se conservan: cada programacion
     * con ubicacion se convierte en una fila de la pivote.
     */
    public function up(): void
    {
        if (! Schema::hasTable('inventory_schedules')) {
            return;
        }

        if (! Schema::hasTable('inventory_schedule_inventory')) {
            Schema::create('inventory_schedule_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_schedule_id')
                    ->constrained('inventory_schedules')
                    ->cascadeOnDelete();
                $table->foreignId('inventory_id')
                    ->constrained('inventories')
                    ->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['inventory_schedule_id', 'inventory_id'], 'schedule_inventory_unique');
            });
        }

        if (! Schema::hasColumn('inventory_schedules', 'inventory_id')) {
            return;
        }

        $now = now();

        DB::table('inventory_schedules')
            ->whereNotNull('inventory_id')
            ->orderBy('id')
            ->chunk(200, function ($schedules) use ($now) {
                DB::table('inventory_schedule_inventory')->insertOrIgnore(
                    $schedules->map(fn ($schedule) => [
                        'inventory_schedule_id' => $schedule->id,
                        'inventory_id' => $schedule->inventory_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('inventory_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('inventory_schedules') && ! Schema::hasColumn('inventory_schedules', 'inventory_id')) {
            Schema::table('inventory_schedules', function (Blueprint $table) {
                $table->foreignId('inventory_id')
                    ->nullable()
                    ->after('is_open')
                    ->constrained('inventories')
                    ->nullOnDelete();
            });

            // Se recupera solo la primera ubicacion: la columna no admite mas.
            if (Schema::hasTable('inventory_schedule_inventory')) {
                DB::table('inventory_schedule_inventory')
                    ->orderBy('inventory_schedule_id')
                    ->orderBy('id')
                    ->get()
                    ->groupBy('inventory_schedule_id')
                    ->each(function ($rows, $scheduleId) {
                        DB::table('inventory_schedules')
                            ->where('id', $scheduleId)
                            ->update(['inventory_id' => $rows->first()->inventory_id]);
                    });
            }
        }

        Schema::dropIfExists('inventory_schedule_inventory');
    }
};
