<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('tenants')) {
            return;
        }

        $tenants = DB::connection('central')
            ->table('tenants')
            ->select('id', 'slug', 'database')
            ->get();

        foreach ($tenants as $tenant) {
            $configuredDatabase = Config::get("tenancy.tenant_credentials.{$tenant->slug}.database");

            if (! is_string($configuredDatabase) || trim($configuredDatabase) === '') {
                continue;
            }

            if ($tenant->database === $configuredDatabase) {
                continue;
            }

            DB::connection('central')
                ->table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'database' => $configuredDatabase,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // No reversible de forma segura.
    }
};
