<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vuelve a asegurar la columna global_role en usuarios de sede.
 *
 * `migrate:tenants` marca como ejecutadas las migraciones pendientes de una base
 * existente sin correrlas, asi que una sede pudo quedar registrada con
 * 2026_04_01_205000_add_global_role_to_users_table_if_missing sin tener la columna.
 * Sin ella no se pueden registrar super administradores en esa sede (role es un
 * enum administrador/consultor).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'global_role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('global_role')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        // No se elimina: la columna pertenece a 2026_04_01_205000_add_global_role_to_users_table_if_missing.
    }
};
