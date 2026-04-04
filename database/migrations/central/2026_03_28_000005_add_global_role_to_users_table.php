<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asegura compatibilidad del campo global_role en la tabla users.
 *
 * En instalaciones nuevas la columna nace desde la migracion base de users.
 * Este archivo queda como respaldo para bases que ya existian antes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('global_role')->nullable()->after('role');
            });
        }

        if (Schema::connection('central')->hasTable('users') && ! Schema::connection('central')->hasColumn('users', 'global_role')) {
            Schema::connection('central')->table('users', function (Blueprint $table) {
                $table->string('global_role')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('central')->hasTable('users') && Schema::connection('central')->hasColumn('users', 'global_role')) {
            Schema::connection('central')->table('users', function (Blueprint $table) {
                $table->dropColumn('global_role');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('global_role');
            });
        }
    }
};
