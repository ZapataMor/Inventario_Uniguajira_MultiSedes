<?php

namespace App\Services\Schedules;

use App\Models\Central\Tenant;
use App\Models\Central\TenantBranding;
use App\Models\InventorySchedule;
use App\Services\Reports\SimplePdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Comprobante en PDF de una labor documentada.
 *
 * Es el mismo documento para las dos rutas de descarga: la persona externa
 * lo obtiene al terminar el formulario publico y el personal de la sede lo
 * descarga desde la tarjeta de la programacion. Por eso la construccion
 * vive aqui y no en un controlador.
 *
 * El comprobante siempre nombra la sede donde se hizo la labor (Maicao,
 * Fonseca, Villanueva...), tomandola del branding del tenant activo.
 */
class ScheduleReceiptService
{
    public function __construct(
        private readonly SimplePdfService $pdfService,
        private readonly ScheduleEvidenceService $evidence,
    ) {}

    /**
     * Respuesta de descarga del comprobante.
     */
    public function download(InventorySchedule $schedule, ?Tenant $tenant = null): Response
    {
        $data = $this->data($schedule, $tenant);

        $html = view('schedules.pdf.comprobante', $data)->render();
        $pdf = $this->pdfService->buildHtml($html, 'A4', 'portrait');

        $fileName = 'comprobante-'.Str::slug($data['receiptCode']).'.pdf';

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Datos que alimentan la plantilla del comprobante.
     *
     * @return array<string, mixed>
     */
    public function data(InventorySchedule $schedule, ?Tenant $tenant = null): array
    {
        $tenant ??= tenant();
        $branding = $tenant?->branding;
        $timezone = $branding?->timezone_value ?? 'America/Bogota';

        $entry = $schedule->relationLoaded('entry') && $schedule->entry
            ? $schedule->entry
            : $schedule->entries()->with('images')->first();

        abort_if($entry === null, 404);

        $entry->loadMissing('images');
        $entry->setRelation('schedule', $schedule);

        $images = $entry->images
            ->map(fn ($image) => [
                'dataUri' => $this->evidence->dataUri($image),
                'description' => $image->description,
            ])
            ->filter(fn (array $image) => $image['dataUri'] !== null)
            ->values();

        $sedeName = $branding?->sede_name ?: ($tenant?->name ?? 'Sede principal');

        return [
            'receiptCode' => $entry->receipt_code,
            'institution' => $branding?->institution_name ?: 'Universidad de La Guajira',
            'sedeName' => $sedeName,
            'sedeLabel' => self::sedeLabel($sedeName),
            'appName' => $branding?->app_name ?: config('app.name', 'Inventario Uniguajira'),
            'logoDataUri' => $this->logoDataUri($branding),
            'footerText' => $branding?->report_footer
                ?? 'Documento generado automáticamente por el sistema de inventario.',
            'issuedAt' => now()->setTimezone($timezone)->format('d/m/Y H:i'),
            'registeredAt' => $entry->registeredAtLabel($timezone),
            'schedule' => $schedule,
            'entry' => $entry,
            'locations' => $schedule->location_labels,
            'images' => $images,
        ];
    }

    /**
     * Nombre de la sede precedido de la palabra "Sede".
     *
     * Algunos brandings ya la incluyen ("Sede Maicao") y otros no
     * ("Maicao"), asi que anteponerla a ciegas produciria duplicados.
     */
    public static function sedeLabel(string $sedeName): string
    {
        return Str::startsWith(Str::lower($sedeName), 'sede')
            ? $sedeName
            : 'Sede '.$sedeName;
    }

    /**
     * Logo institucional en base64 para incrustarlo en el PDF.
     */
    private function logoDataUri(?TenantBranding $branding): ?string
    {
        $path = public_path($branding?->logo_report ?: 'assets/images/logoUniguajira.png');

        if (! is_file($path)) {
            return null;
        }

        $binary = @file_get_contents($path);

        if ($binary === false) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }
}
