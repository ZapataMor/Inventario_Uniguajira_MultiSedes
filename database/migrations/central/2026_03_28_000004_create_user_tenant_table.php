<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membresías usuario-sede.
 *
 * Asocia usuarios globales con sedes específicas y les asigna
 * un rol dentro de esa sede. Un usuario puede pertenecer a
 * múltiples sedes con roles distintos.
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('user_tenant', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role')->default('consultor');   // 'administrador', 'consultor'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('user_tenant');
    }
};
