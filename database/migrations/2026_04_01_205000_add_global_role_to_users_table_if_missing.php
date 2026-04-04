<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asegura la columna global_role en usuarios de sede.
 *
 * Esta columna es necesaria para identificar super administradores
 * creados/sincronizados desde el portal central.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('global_role')->nullable()->after('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'global_role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('global_role');
            });
        }
    }
};
