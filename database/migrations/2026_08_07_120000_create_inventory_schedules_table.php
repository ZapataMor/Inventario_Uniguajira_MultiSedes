<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Programaciones de inventario o mantenimiento.
     *
     * Solo guardan el nombre con el que se identifica la labor y, de forma
     * opcional, el inventario donde se realizara. El codigo publico unico
     * alimenta el QR y el enlace del formulario externo.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventory_schedules')) {
            return;
        }

        Schema::create('inventory_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->boolean('is_open')->default(true);
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_schedules');
    }
};
