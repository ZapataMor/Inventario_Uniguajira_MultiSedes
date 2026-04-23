<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $tenantId = DB::connection('central')
            ->table('tenants')
            ->where('slug', 'maicao')
            ->value('id');

        if (! $tenantId) {
            return;
        }

        DB::connection('central')
            ->table('tenant_branding')
            ->where('tenant_id', $tenantId)
            ->update([
                'login_background' => 'images/fondounigua.png',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $tenantId = DB::connection('central')
            ->table('tenants')
            ->where('slug', 'maicao')
            ->value('id');

        if (! $tenantId) {
            return;
        }

        DB::connection('central')
            ->table('tenant_branding')
            ->where('tenant_id', $tenantId)
            ->update([
                'login_background' => 'images/fondo-uniguajira.jpeg',
                'updated_at' => now(),
            ]);
    }
};
