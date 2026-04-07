<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajusta los dominios de tenants al formato con guiones:
 * {slug}-inventario.{base_domain}
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('tenants')) {
            return;
        }

        if (! Schema::connection('central')->hasTable('domains')) {
            return;
        }

        $baseDomain = config('tenancy.base_domain', 'localhost');

        $tenants = DB::connection('central')
            ->table('tenants')
            ->select('id', 'slug')
            ->get();

        foreach ($tenants as $tenant) {
            $domain = "{$tenant->slug}-inventario.{$baseDomain}";

            $primaryQuery = DB::connection('central')
                ->table('domains')
                ->where('tenant_id', $tenant->id)
                ->where('is_primary', true);

            if ($primaryQuery->exists()) {
                $primaryQuery->update(['domain' => $domain]);
                continue;
            }

            DB::connection('central')
                ->table('domains')
                ->where('tenant_id', $tenant->id)
                ->update(['domain' => $domain]);
        }
    }

    public function down(): void
    {
        // No reversible de forma segura (pueden existir dominios personalizados).
    }
};
