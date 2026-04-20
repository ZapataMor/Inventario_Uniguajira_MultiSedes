<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
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

        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('tenants', 'host')) {
                $table->string('host')->nullable()->after('database');
            }

            if (! Schema::connection('central')->hasColumn('tenants', 'port')) {
                $table->unsignedInteger('port')->nullable()->after('host');
            }

            if (! Schema::connection('central')->hasColumn('tenants', 'username')) {
                $table->string('username')->nullable()->after('port');
            }

            if (! Schema::connection('central')->hasColumn('tenants', 'password')) {
                $table->text('password')->nullable()->after('username');
            }
        });

        $tenantDbHost = Config::get('tenancy.tenant_db_host');
        $tenantDbPort = Config::get('tenancy.tenant_db_port');

        $tenants = DB::connection('central')
            ->table('tenants')
            ->select('id', 'slug', 'host', 'port', 'username', 'password')
            ->get();

        foreach ($tenants as $tenant) {
            $legacy = (array) Config::get("tenancy.tenant_credentials.{$tenant->slug}", []);
            $updates = [];

            if (($tenant->host === null || $tenant->host === '') && ! empty($legacy['host'])) {
                $updates['host'] = $legacy['host'];
            } elseif (($tenant->host === null || $tenant->host === '') && $tenantDbHost) {
                $updates['host'] = $tenantDbHost;
            }

            if ($tenant->port === null && ! empty($legacy['port'])) {
                $updates['port'] = (int) $legacy['port'];
            } elseif ($tenant->port === null && $tenantDbPort) {
                $updates['port'] = (int) $tenantDbPort;
            }

            if (($tenant->username === null || $tenant->username === '') && ! empty($legacy['username'])) {
                $updates['username'] = $legacy['username'];
            }

            if ($tenant->password === null && array_key_exists('password', $legacy) && $legacy['password'] !== null && $legacy['password'] !== '') {
                $updates['password'] = Crypt::encryptString((string) $legacy['password']);
            }

            if ($updates !== []) {
                DB::connection('central')
                    ->table('tenants')
                    ->where('id', $tenant->id)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('tenants')) {
            return;
        }

        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $columns = [];

            foreach (['host', 'port', 'username', 'password'] as $column) {
                if (Schema::connection('central')->hasColumn('tenants', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
