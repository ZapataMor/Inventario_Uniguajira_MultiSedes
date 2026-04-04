<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de sedes/tenants en la base central.
 *
 * Cada registro representa una sede de la Universidad de la Guajira
 * con su propia base de datos operativa.
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // "Sede Maicao"
            $table->string('slug')->unique();           // "maicao"
            $table->string('database')->unique();       // "inventario_maicao"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
