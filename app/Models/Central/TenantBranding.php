<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración de branding visual por sede.
 *
 * Almacena nombre institucional, logos, colores, textos de reportes,
 * favicon y cualquier personalización visual por sede.
 */
class TenantBranding extends Model
{
    protected $connection = 'central';

    protected $table = 'tenant_branding';

    protected $fillable = [
        'tenant_id',
        'app_name',
        'institution_name',
        'sede_name',
        'logo_navbar',
        'logo_sidebar',
        'logo_report',
        'logo_login',
        'favicon',
        'login_background',
        'login_welcome_text',
        'primary_color',
        'secondary_color',
        'report_footer_text',
        'report_header_text',
        'timezone',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ─── Accessors con defaults ──────────────────────────────────

    /**
     * Devuelve el nombre para mostrar en la app (ej: "Inventario Maicao").
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->app_name
            ?? $this->institution_name . ' - ' . $this->sede_name
            ?? config('app.name');
    }

    /**
     * Devuelve el texto de footer para reportes PDF.
     */
    public function getReportFooterAttribute(): string
    {
        return $this->report_footer_text
            ?? "Este documento es un reporte generado automáticamente por el sistema de {$this->display_name}.";
    }

    /**
     * Devuelve la ruta del logo para reportes, con fallback.
     */
    public function getReportLogoPathAttribute(): string
    {
        if ($this->logo_report) {
            return public_path($this->logo_report);
        }

        return public_path('assets/images/logoUniguajira.png');
    }

    /**
     * Timezone configurado para esta sede.
     */
    public function getTimezoneValueAttribute(): string
    {
        return $this->timezone ?? 'America/Bogota';
    }
}
