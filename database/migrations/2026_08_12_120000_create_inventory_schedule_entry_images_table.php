<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Evidencias fotograficas adjuntas a una labor documentada.
     *
     * Las imagenes viven en el storage de la sede; aqui solo se guarda
     * la ruta relativa, la descripcion opcional y el orden en que la
     * persona externa las adjunto (ese mismo orden se respeta en el
     * comprobante en PDF).
     *
     * Los nombres de la llave foranea y del indice se acortan a mano:
     * el nombre que Laravel genera por convencion supera los 64
     * caracteres que admite MySQL.
     */
    public function up(): void
    {
        if (Schema::hasTable('inventory_schedule_entry_images')) {
            return;
        }

        Schema::create('inventory_schedule_entry_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_schedule_entry_id');
            $table->string('path');
            $table->string('description')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('inventory_schedule_entry_id', 'ise_images_entry_idx');

            $table->foreign('inventory_schedule_entry_id', 'ise_images_entry_fk')
                ->references('id')
                ->on('inventory_schedule_entries')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_schedule_entry_images');
    }
};
