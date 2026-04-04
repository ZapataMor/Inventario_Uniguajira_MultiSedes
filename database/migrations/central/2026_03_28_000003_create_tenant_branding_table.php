<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branding visual por sede.
 *
 * Almacena toda la personalización visual: logos, colores,
 * textos institucionales, favicon, fondo de login, etc.
 */
return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_branding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->cascadeOnDelete();

            // Nombres
            $table->string('app_name')->nullable();             // "Inventario Maicao"
            $table->string('institution_name')->nullable();     // "Universidad de La Guajira"
            $table->string('sede_name')->nullable();            // "Sede Maicao"

            // Logos y assets visuales
            $table->string('logo_navbar')->nullable();          // Ruta relativa al logo del navbar
            $table->string('logo_sidebar')->nullable();         // Ruta relativa al logo del sidebar
            $table->string('logo_report')->nullable();          // Ruta relativa al logo para reportes PDF
            $table->string('logo_login')->nullable();           // Ruta relativa al logo en la pantalla de login
            $table->string('favicon')->nullable();              // Ruta relativa al favicon
            $table->string('login_background')->nullable();     // Ruta relativa a la imagen de fondo del login

            // Textos
            $table->text('login_welcome_text')->nullable();     // Texto de bienvenida en login
            $table->text('report_footer_text')->nullable();     // Footer de reportes PDF
            $table->text('report_header_text')->nullable();     // Header de reportes PDF

            // Colores (Tailwind tokens o hex)
            $table->string('primary_color', 50)->nullable();    // "emerald-600"
            $table->string('secondary_color', 50)->nullable();  // "emerald-700"

            // Configuración regional
            $table->string('timezone', 50)->default('America/Bogota');

            // Campo JSON flexible para extensiones futuras
            $table->json('extra')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_branding');
    }
};
