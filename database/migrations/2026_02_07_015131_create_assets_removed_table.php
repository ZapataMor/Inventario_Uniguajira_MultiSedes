<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets_removed', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // SIN unique
            $table->enum('type', ['Cantidad', 'Serial']);
            $table->string('image')->nullable();
            $table->integer('quantity')->default(0);
            $table->text('reason')->nullable();  // Campo para el motivo
            $table->foreignId('asset_id')->constrained('assets');
            $table->foreignId('inventory_id')->constrained('inventories');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets_removed');
    }
};