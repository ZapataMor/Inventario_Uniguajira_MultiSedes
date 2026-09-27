<?php

namespace App\Services\Schedules;

use App\Models\Central\Tenant;
use App\Models\InventorySchedule;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

/**
 * Formato RA-F-33 "Solicitud de servicio" diligenciado, en PDF.
 *
 * El documento no se maqueta: se parte del formato institucional tal cual
 * (resources/pdf-templates/ra-f-33-solicitud-de-servicio.pdf, exportado
 * desde el .docx original) y sobre esa pagina se escriben los datos en sus
 * lineas y se marcan las casillas con una X. Asi la estructura, los logos,
 * el encabezado y el pie son exactamente los del formato.
 *
 * Las coordenadas estan en puntos (1/72") sobre la pagina carta horizontal
 * de la plantilla y se midieron sobre ella. Si el formato cambia de
 * revision, hay que volver a exportar la plantilla y medirlas de nuevo.
 *
 * Firmas:
 *  - "Actividad realizada por": la firma que la persona externa dibujo en
 *    el formulario publico. Va siempre que exista.
 *  - "Actividad recibida por": nombre y firma de quien descarga desde la
 *    sede. Solo se estampa en la descarga firmada; la vista previa y la
 *    copia de la persona externa la dejan en blanco.
 */
class ScheduleReceiptService
{
    public function __construct(
        private readonly ScheduleSignatureService $signatures,
    ) {}

    private const TEMPLATE = 'pdf-templates/ra-f-33-solicitud-de-servicio.pdf';

    private const FONT = 'Helvetica';

    private const FONT_SIZE = 9.5;

    /** Tamano minimo al que se reduce un texto largo para que quepa en su linea. */
    private const MIN_FONT_SIZE = 5.5;

    /**
     * Lineas de una sola fila: [x inicial, y superior de la fila, x final].
     */
    private const LINES = [
        'requester_name' => [110.0, 101.7, 476.0],
        'filing_number' => [614.0, 101.7, 690.0],
        'requester_position' => [111.5, 115.1, 477.0],
        'requester_dependency' => [133.5, 128.5, 478.0],
        'requested_at' => [566.0, 128.5, 658.0],
        'equipment_name' => [163.0, 326.6, 314.0],
        'equipment_model' => [361.0, 326.6, 503.0],
        'equipment_brand' => [542.5, 326.6, 700.0],
        'location' => [135.0, 346.1, 740.0],
        'materials' => [125.5, 399.9, 738.0],
        'performed_by' => [189.0, 431.3, 462.0],
        'received_by' => [189.0, 458.1, 462.0],
    ];

    /**
     * Espacio de cada firma: [x0, y0, x1, y1]. Cubre la linea "FIRMA____"
     * (x 529-713) sin invadir la barra "REPORTE DEL SERVICIO" (termina en
     * y 425.6), la otra firma ni el borde inferior de la tabla (y 469.4).
     */
    private const SIGNATURE_AREAS = [
        'performed' => [531.0, 426.5, 711.0, 446.0],
        'received' => [531.0, 448.0, 711.0, 468.8],
    ];

    /**
     * Las tres lineas de "ACCION": la primera empieza despues del rotulo.
     */
    private const ACTION_LINES = [
        [104.5, 359.6, 733.0],
        [63.5, 373.0, 736.0],
        [63.5, 386.5, 736.0],
    ];

    /**
     * Centro de cada casilla de "Tipo de servicio solicitado".
     */
    private const SERVICE_BOXES = [
        'acometida_electrica' => [208.9, 180.9],
        'equipos_telefonicos' => [355.5, 180.9],
        'paredes' => [466.6, 180.9],
        'ventiladores' => [578.8, 180.9],
        'aires_acondicionados' => [208.9, 197.5],
        'escritorios' => [355.5, 197.5],
        'puertas' => [466.6, 197.5],
        'baterias_sanitarias' => [208.9, 215.2],
        'interruptores' => [355.5, 215.2],
        'tableros' => [466.6, 215.2],
        'breaker' => [208.9, 232.9],
        'lamparas' => [355.5, 232.9],
        'toma_corriente' => [466.6, 232.9],
        'cortinas' => [208.9, 249.7],
        'mesas' => [355.5, 249.7],
        'sillas' => [466.6, 249.7],
    ];

    private const OTHER_BOX = [578.8, 197.5];

    /** Las casillas de la ultima columna son mas angostas que las demas. */
    private const NARROW_BOXES = ['ventiladores'];

    /** Recuadro donde se describe el "otro" servicio: [x0, y0, x1, y1]. */
    private const OTHER_AREA = [497.0, 211.0, 729.0, 254.0];

    private const ACTIVITY_BOXES = [
        'mantenimiento' => [290.3, 284.5],
        'servicio_general' => [423.2, 284.2],
    ];

    private const MAINTENANCE_BOXES = [
        'preventivo' => [290.3, 297.1],
        'correctivo' => [423.8, 296.4],
    ];

    /**
     * Respuesta de descarga del formato diligenciado.
     *
     * @param  array{name: string, signature: string}|null  $receiver  quien recibe
     *                                                                 (firma en PNG)
     */
    public function download(InventorySchedule $schedule, ?Tenant $tenant = null, ?array $receiver = null): Response
    {
        return $this->respond($schedule, $receiver, 'attachment');
    }

    /**
     * El mismo documento, pero para verlo en el navegador (vista previa).
     */
    public function preview(InventorySchedule $schedule): Response
    {
        return $this->respond($schedule, null, 'inline');
    }

    private function respond(InventorySchedule $schedule, ?array $receiver, string $disposition): Response
    {
        $fileName = 'RA-F-33-solicitud-de-servicio-'.Str::slug($schedule->code).'.pdf';

        return new Response($this->render($schedule, $receiver), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$fileName.'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Binario del PDF: la plantilla con los datos escritos encima.
     *
     * @param  array{name: string, signature: string}|null  $receiver
     */
    public function render(InventorySchedule $schedule, ?array $receiver = null): string
    {
        $entry = $schedule->relationLoaded('entry') && $schedule->entry
            ? $schedule->entry
            : $schedule->entries()->first();

        abort_if($entry === null, 404);

        $schedule->loadMissing('inventories.group');

        $pdf = new Fpdi('L', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->setSourceFile(resource_path(self::TEMPLATE));

        $template = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($template);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($template);
        $pdf->SetTextColor(0, 0, 0);

        // Informacion del solicitante
        $this->line($pdf, 'requester_name', $schedule->requester_name);
        $this->line($pdf, 'filing_number', $schedule->filing_number);
        $this->line($pdf, 'requester_position', $schedule->requester_position);
        $this->line($pdf, 'requester_dependency', $schedule->requester_dependency);
        $this->line($pdf, 'requested_at', $schedule->requested_at?->format('d/m/Y'));

        // Tipo de servicio solicitado
        foreach ($schedule->service_types ?? [] as $service) {
            if (isset(self::SERVICE_BOXES[$service])) {
                $this->check($pdf, self::SERVICE_BOXES[$service], in_array($service, self::NARROW_BOXES, true));
            }
        }

        if (filled($schedule->service_other)) {
            $this->check($pdf, self::OTHER_BOX, narrow: true);
            $this->area($pdf, self::OTHER_AREA, $schedule->service_other);
        }

        // Descripcion de la actividad
        if (isset(self::ACTIVITY_BOXES[$schedule->activity_type])) {
            $this->check($pdf, self::ACTIVITY_BOXES[$schedule->activity_type]);
        }

        if (isset(self::MAINTENANCE_BOXES[$schedule->maintenance_type])) {
            $this->check($pdf, self::MAINTENANCE_BOXES[$schedule->maintenance_type]);
        }

        if ($entry->is_equipment) {
            $this->line($pdf, 'equipment_name', $entry->equipment_name);
            $this->line($pdf, 'equipment_model', $entry->equipment_model);
            $this->line($pdf, 'equipment_brand', $entry->equipment_brand);
        }

        $this->line($pdf, 'location', $this->location($schedule));
        $this->paragraph($pdf, self::ACTION_LINES, $entry->action);
        $this->line($pdf, 'materials', $this->singleLine($entry->materials));

        // Reporte del servicio
        $this->line($pdf, 'performed_by', $entry->performed_by);

        if ($performerSignature = $this->signatures->performerFile($schedule)) {
            $this->signature($pdf, self::SIGNATURE_AREAS['performed'], $performerSignature);
        }

        if ($receiver !== null) {
            $this->line($pdf, 'received_by', $receiver['name']);
            $this->signatureFromBinary($pdf, self::SIGNATURE_AREAS['received'], $receiver['signature']);
        }

        return $pdf->Output('S');
    }

    /**
     * Estampa una firma (PNG con transparencia) dentro de su espacio,
     * conservando la proporcion y centrada sobre la linea.
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}  $area
     */
    private function signature(Fpdi $pdf, array $area, string $file): void
    {
        $info = @getimagesize($file);

        if (! $info || $info[0] < 1 || $info[1] < 1) {
            return;
        }

        [$x0, $y0, $x1, $y1] = $area;
        $scale = min(($x1 - $x0) / $info[0], ($y1 - $y0) / $info[1]);
        $width = $info[0] * $scale;
        $height = $info[1] * $scale;

        $pdf->Image(
            $file,
            $x0 + (($x1 - $x0) - $width) / 2,
            $y1 - $height,
            $width,
            $height,
            'PNG'
        );
    }

    private function signatureFromBinary(Fpdi $pdf, array $area, string $png): void
    {
        $file = tempnam(sys_get_temp_dir(), 'firma');

        try {
            file_put_contents($file, $png);
            $this->signature($pdf, $area, $file);
        } finally {
            @unlink($file);
        }
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
     * "Bloque — Salon 1, Salon 2".
     */
    private function location(InventorySchedule $schedule): string
    {
        $rooms = $schedule->inventories;
        $block = $rooms->first()?->group?->name;

        return implode(' — ', array_filter([$block, $rooms->pluck('name')->implode(', ')]));
    }

    /**
     * Escribe un texto sobre una linea del formato, reduciendo la letra
     * si no cabe en el largo de la linea.
     */
    private function line(Fpdi $pdf, string $field, ?string $text): void
    {
        $text = $this->encode($text);

        if ($text === '') {
            return;
        }

        [$x, $top, $end] = self::LINES[$field];

        $this->fitFont($pdf, $text, $end - $x);
        $pdf->Text($x, $this->baseline($top), $this->truncate($pdf, $text, $end - $x));
    }

    /**
     * Reparte un texto largo en las lineas disponibles (las de "Accion").
     * Si no cabe, se reduce la letra antes de recortarlo.
     *
     * @param  array<int, array{0: float, 1: float, 2: float}>  $lines
     */
    private function paragraph(Fpdi $pdf, array $lines, ?string $text): void
    {
        $text = $this->encode($this->singleLine($text));

        if ($text === '') {
            return;
        }

        $size = self::FONT_SIZE;

        do {
            $pdf->SetFont(self::FONT, '', $size);
            $rows = $this->wrap($pdf, $text, array_map(fn ($line) => $line[2] - $line[0], $lines));
            $fits = count($rows) <= count($lines);
            $size -= 0.5;
        } while (! $fits && $size >= self::MIN_FONT_SIZE);

        foreach (array_slice($rows, 0, count($lines)) as $index => $row) {
            [$x, $top, $end] = $lines[$index];

            // La ultima linea se recorta con "..." si aun asi no cupo todo.
            if (! $fits && $index === count($lines) - 1) {
                $row = $this->truncate($pdf, $row.' '.implode(' ', array_slice($rows, count($lines))), $end - $x);
            }

            $pdf->Text($x, $this->baseline($top), $row);
        }
    }

    /**
     * Texto libre dentro de un recuadro (el de "Otros").
     *
     * @param  array{0: float, 1: float, 2: float, 3: float}  $area
     */
    private function area(Fpdi $pdf, array $area, string $text): void
    {
        [$x0, $y0, $x1, $y1] = $area;

        $pdf->SetFont(self::FONT, '', 8.5);
        $pdf->SetXY($x0, $y0);
        $pdf->MultiCell($x1 - $x0, 10, $this->encode($this->singleLine($text)), 0, 'L');
    }

    /**
     * Marca una casilla con una X centrada.
     *
     * @param  array{0: float, 1: float}  $center
     */
    private function check(Fpdi $pdf, array $center, bool $narrow = false): void
    {
        [$x, $y] = $center;

        $size = $narrow ? 8 : 10;

        $pdf->SetFont(self::FONT, 'B', $size);
        $pdf->Text($x - $pdf->GetStringWidth('X') / 2, $y + $size * 0.36, 'X');
    }

    /**
     * Divide el texto en filas segun el ancho de cada linea disponible.
     *
     * @param  array<int, float>  $widths
     * @return array<int, string>
     */
    private function wrap(Fpdi $pdf, string $text, array $widths): array
    {
        $rows = [];
        $current = '';

        foreach (preg_split('/\s+/', $text) as $word) {
            $width = $widths[min(count($rows), count($widths) - 1)];
            $candidate = $current === '' ? $word : $current.' '.$word;

            if ($pdf->GetStringWidth($candidate) <= $width || $current === '') {
                $current = $candidate;

                continue;
            }

            $rows[] = $current;
            $current = $word;
        }

        if ($current !== '') {
            $rows[] = $current;
        }

        return $rows;
    }

    private function fitFont(Fpdi $pdf, string $text, float $width): void
    {
        $size = self::FONT_SIZE;
        $pdf->SetFont(self::FONT, '', $size);

        while ($pdf->GetStringWidth($text) > $width && $size > self::MIN_FONT_SIZE) {
            $size -= 0.5;
            $pdf->SetFont(self::FONT, '', $size);
        }
    }

    private function truncate(Fpdi $pdf, string $text, float $width): string
    {
        if ($pdf->GetStringWidth($text) <= $width) {
            return $text;
        }

        while ($text !== '' && $pdf->GetStringWidth($text.'...') > $width) {
            $text = substr($text, 0, -1);
        }

        return rtrim($text).'...';
    }

    /**
     * El texto se apoya sobre el subrayado de la fila, como escrito a mano.
     */
    private function baseline(float $top): float
    {
        return $top + 8.6;
    }

    private function singleLine(?string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $text));
    }

    /**
     * Las fuentes base de FPDF trabajan en Windows-1252.
     */
    private function encode(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);

        return $converted === false ? $text : $converted;
    }
}
