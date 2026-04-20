<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed inicial de las tres sedes.
 *
 * Crea los registros de tenant para Maicao (activo), Villanueva y Fonseca
 * (inactivos hasta que se configuren sus bases de datos).
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $now = now();
        $maicaoDatabase = config('tenancy.tenant_credentials.maicao.database', 'inventario_maicao');
        $villanuevaDatabase = config('tenancy.tenant_credentials.villanueva.database', 'inventario_villanueva');
        $fonsecaDatabase = config('tenancy.tenant_credentials.fonseca.database', 'inventario_fonseca');

        // Crear tenants
        DB::connection('central')->table('tenants')->insert([
            [
                'name' => 'Sede Maicao',
                'slug' => 'maicao',
                'database' => $maicaoDatabase,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sede Villanueva',
                'slug' => 'villanueva',
                'database' => $villanuevaDatabase,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sede Fonseca',
                'slug' => 'fonseca',
                'database' => $fonsecaDatabase,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $maicaoId = DB::connection('central')->table('tenants')
            ->where('slug', 'maicao')->value('id');
        $villanuvaId = DB::connection('central')->table('tenants')
            ->where('slug', 'villanueva')->value('id');
        $fonsecaId = DB::connection('central')->table('tenants')
            ->where('slug', 'fonseca')->value('id');

        // Crear dominios por defecto (localhost para desarrollo)
        DB::connection('central')->table('domains')->insert([
            [
                'tenant_id' => $maicaoId,
                'domain' => 'maicao.localhost',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $villanuvaId,
                'domain' => 'villanueva.localhost',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tenant_id' => $fonsecaId,
                'domain' => 'fonseca.localhost',
                'is_primary' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // Crear branding para Maicao (datos actuales)
        DB::connection('central')->table('tenant_branding')->insert([
            'tenant_id' => $maicaoId,
            'app_name' => 'Inventario Uniguajira',
            'institution_name' => 'Universidad de La Guajira',
            'sede_name' => 'Sede Maicao',
            'logo_navbar' => 'assets/images/logo-uniguajira-blanco.webp',
            'logo_report' => 'assets/images/logoUniguajira.png',
            'logo_login' => 'assets/images/logo-uniguajira-seo-150x150.webp',
            'favicon' => 'assets/images/favicon-uniguajira-32x32.webp',
            'login_background' => 'images/fondo-uniguajira.jpeg',
            'login_welcome_text' => 'Gestiona el inventario de la Universidad y mantén el control de tus activos.',
            'report_footer_text' => 'Este documento es un reporte generado automáticamente por el sistema de Inventario Uniguajira sede Maicao.',
            'report_header_text' => 'UNIGUAJIRA MAICAO',
            'primary_color' => 'emerald-600',
            'secondary_color' => 'emerald-700',
            'timezone' => 'America/Bogota',
            'extra' => json_encode(['logo_secondary_navbar' => 'assets/images/Diseño4-1.png']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Branding placeholder para Villanueva
        DB::connection('central')->table('tenant_branding')->insert([
            'tenant_id' => $villanuvaId,
            'app_name' => 'Inventario Villanueva',
            'institution_name' => 'Universidad de La Guajira',
            'sede_name' => 'Sede Villanueva',
            'login_background' => 'assets/images/sede-villanueva.png',
            'login_welcome_text' => 'Gestiona el inventario de la sede Villanueva.',
            'report_footer_text' => 'Este documento es un reporte generado automáticamente por el sistema de Inventario Uniguajira sede Villanueva.',
            'report_header_text' => 'UNIGUAJIRA VILLANUEVA',
            'primary_color' => 'emerald-600',
            'secondary_color' => 'emerald-700',
            'timezone' => 'America/Bogota',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Branding placeholder para Fonseca
        DB::connection('central')->table('tenant_branding')->insert([
            'tenant_id' => $fonsecaId,
            'app_name' => 'Inventario Fonseca',
            'institution_name' => 'Universidad de La Guajira',
            'sede_name' => 'Sede Fonseca',
            'login_background' => 'assets/images/sede-fonseca.webp',
            'login_welcome_text' => 'Gestiona el inventario de la sede Fonseca.',
            'report_footer_text' => 'Este documento es un reporte generado automáticamente por el sistema de Inventario Uniguajira sede Fonseca.',
            'report_header_text' => 'UNIGUAJIRA FONSECA',
            'primary_color' => 'emerald-600',
            'secondary_color' => 'emerald-700',
            'timezone' => 'America/Bogota',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::connection('central')->table('tenant_branding')->truncate();
        DB::connection('central')->table('domains')->truncate();
        DB::connection('central')->table('tenants')->truncate();
    }
};
