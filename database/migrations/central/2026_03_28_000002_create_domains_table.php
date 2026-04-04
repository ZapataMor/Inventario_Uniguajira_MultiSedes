<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dominios/subdominios asociados a cada sede.
 *
 * Permite que cada sede tenga uno o más dominios de acceso:
 * - maicao.inventario.uniguajira.edu.co (subdominio)
 * - inventario-maicao.uniguajira.edu.co (dominio propio)
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('domain')->unique();         // "maicao.inventario.uniguajira.edu.co"
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('domains');
    }
};
