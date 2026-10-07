<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Niveles de super administrador: los super administradores existentes pasan a
 * "super_consultor" (todas las sedes, solo lectura), que es lo que podian hacer
 * hasta ahora. El principal (recursosfisicos) queda siempre como "super_administrador".
 *
 * Existe una copia en database/migrations/central para la base central.
 */
return new class extends Migration
{
    private const ROOT_ADMIN_EMAIL = 'recursosfisicos@uniguajira.edu.co';

    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'global_role')) {
            return;
        }

        DB::table('users')
            ->where(function ($query) {
                $query->where('global_role', 'super_administrador')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('global_role')->where('role', 'super_administrador');
                    });
            })
            ->whereRaw('LOWER(email) <> ?', [self::ROOT_ADMIN_EMAIL])
            ->update(['global_role' => 'super_consultor']);

        DB::table('users')
            ->whereRaw('LOWER(email) = ?', [self::ROOT_ADMIN_EMAIL])
            ->update(['global_role' => 'super_administrador']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'global_role')) {
            return;
        }

        DB::table('users')
            ->where('global_role', 'super_consultor')
            ->update(['global_role' => 'super_administrador']);
    }
};
