<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Labores documentadas desde el formulario publico (QR / enlace).
     *
     * Cada registro lo diligencia una persona externa al aplicativo,
     * por eso se conserva la IP y el user agent como evidencia minima.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventory_schedule_entries')) {
            return;
        }

        Schema::create('inventory_schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_schedule_id')
                ->constrained('inventory_schedules')
                ->cascadeOnDelete();
            $table->string('work_name');
            $table->text('description')->nullable();
            $table->string('responsible_name');
            $table->dateTime('started_at');
            $table->dateTime('finished_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_schedule_entries');
    }
};
