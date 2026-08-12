<?php

namespace App\Services\Schedules;

use App\Models\InventorySchedule;
use App\Models\InventoryScheduleEntry;
use App\Models\InventoryScheduleEntryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Evidencias fotograficas de las labores documentadas.
 *
 * Las imagenes se suben de a una desde el formulario publico, antes de
 * enviarlo. Ese envio escalonado es lo que permite adjuntar tantas fotos
 * como haga falta: un unico POST con todos los archivos chocaria contra
 * `post_max_size` y `max_file_uploads` de PHP mucho antes.
 *
 * Cada subida devuelve un token (el nombre del archivo ya guardado). Al
 * enviar el formulario solo viajan esos tokens y sus descripciones, y es
 * ahi donde se crean las filas en base de datos.
 */
class ScheduleEvidenceService
{
    /** Lado maximo de la imagen almacenada. */
    private const MAX_SIDE = 1800;

    /** Lado maximo de la copia que se incrusta en el comprobante PDF. */
    private const PDF_MAX_SIDE = 900;

    /** Un token es siempre `{uuid}.{extension}`: nunca una ruta. */
    private const TOKEN_PATTERN = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\.(jpg|jpeg|png|webp)$/';

    /**
     * Carpeta de la programacion dentro del storage de la sede.
     */
    public function directory(InventorySchedule $schedule): string
    {
        return tenant_asset('schedules/'.$schedule->id);
    }

    /**
     * Guarda un archivo recien subido y devuelve su token.
     *
     * @return array{token: string, size: int, mime: string, original_name: string}
     */
    public function storeUpload(InventorySchedule $schedule, UploadedFile $file): array
    {
        $original = (string) $file->getClientOriginalName();
        $binary = (string) file_get_contents($file->getRealPath());

        [$binary, $extension, $mime] = $this->normalize(
            $binary,
            strtolower($file->getClientOriginalExtension() ?: 'jpg'),
            (string) ($file->getMimeType() ?: 'image/jpeg')
        );

        $token = Str::uuid()->toString().'.'.$extension;

        Storage::disk('local')->put($this->directory($schedule).'/'.$token, $binary);

        return [
            'token' => $token,
            'size' => strlen($binary),
            'mime' => $mime,
            'original_name' => Str::limit($original, 240, ''),
        ];
    }

    /**
     * Convierte los tokens enviados con el formulario en filas de evidencia.
     *
     * Los tokens que no correspondan a un archivo real de esta programacion
     * se ignoran en silencio: el formulario nunca debe fallar por una foto
     * que se perdio a mitad de camino.
     *
     * @param  array<int, array{token?: string, description?: string}>  $rows
     */
    public function attach(InventoryScheduleEntry $entry, InventorySchedule $schedule, array $rows): int
    {
        $directory = $this->directory($schedule);
        $disk = Storage::disk('local');
        $order = 0;

        foreach ($rows as $row) {
            $token = (string) ($row['token'] ?? '');

            if (! preg_match(self::TOKEN_PATTERN, $token)) {
                continue;
            }

            $path = $directory.'/'.$token;

            if (! $disk->exists($path)) {
                continue;
            }

            $description = trim((string) ($row['description'] ?? ''));

            InventoryScheduleEntryImage::create([
                'inventory_schedule_entry_id' => $entry->id,
                'path' => $path,
                'description' => $description === '' ? null : Str::limit($description, 250, ''),
                'original_name' => Str::limit((string) ($row['original_name'] ?? ''), 240, '') ?: null,
                'mime_type' => $disk->mimeType($path) ?: null,
                'size' => $disk->size($path),
                'sort_order' => $order++,
            ]);
        }

        return $order;
    }

    /**
     * Ruta absoluta de una evidencia, o null si el archivo ya no existe.
     */
    public function absolutePath(InventoryScheduleEntryImage $image): ?string
    {
        $disk = Storage::disk('local');

        return $disk->exists($image->path) ? $disk->path($image->path) : null;
    }

    /**
     * Version reducida en base64 para incrustarla en el comprobante PDF.
     *
     * Dompdf no puede leer archivos por ruta de forma fiable en todos los
     * entornos, y las fotos originales pesarian demasiado dentro del PDF.
     */
    public function dataUri(InventoryScheduleEntryImage $image): ?string
    {
        $path = $this->absolutePath($image);

        if ($path === null) {
            return null;
        }

        $binary = @file_get_contents($path);

        if ($binary === false) {
            return null;
        }

        [$binary, , $mime] = $this->normalize(
            $binary,
            strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg'),
            (string) ($image->mime_type ?: 'image/jpeg'),
            self::PDF_MAX_SIDE,
            70
        );

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * Borra la carpeta completa de una programacion (incluidas las fotos
     * que se subieron pero nunca llegaron a enviarse con el formulario).
     */
    public function purge(InventorySchedule $schedule): void
    {
        Storage::disk('local')->deleteDirectory($this->directory($schedule));
    }

    /**
     * Reescala y reencoda la imagen a JPEG.
     *
     * Normalizar el formato garantiza que la foto se vea igual en el
     * navegador y dentro del PDF. Si GD no esta disponible se devuelve el
     * archivo tal cual llego: es preferible una foto pesada a ninguna.
     *
     * @return array{0: string, 1: string, 2: string} binario, extension y mime
     */
    private function normalize(
        string $binary,
        string $extension,
        string $mime,
        int $maxSide = self::MAX_SIDE,
        int $quality = 82
    ): array {
        $fallbackExtension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $fallback = [$binary, $fallbackExtension, $mime];

        if (! function_exists('imagecreatefromstring')) {
            return $fallback;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return $fallback;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = min(1, $maxSide / max($width, $height));

        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Fondo blanco: al pasar a JPEG, la transparencia de un PNG
        // se volveria negra y arruinaria la evidencia.
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        $encoded = imagejpeg($canvas, null, $quality);
        $output = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $encoded && $output !== ''
            ? [$output, 'jpg', 'image/jpeg']
            : $fallback;
    }
}
