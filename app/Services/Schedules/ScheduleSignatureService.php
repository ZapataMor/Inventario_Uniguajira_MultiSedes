<?php

namespace App\Services\Schedules;

use App\Models\InventorySchedule;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Firmas manuscritas del formato RA-F-33.
 *
 * Se dibujan en el navegador (dedo, lapiz o mouse) y llegan como data URL
 * PNG. Aqui se validan, se recortan al trazo y se guardan como archivo:
 *
 *  - La de quien realizo la actividad (formulario publico) vive en la
 *    carpeta de la programacion, junto a sus evidencias, y se borra con
 *    ella (ScheduleEvidenceService::purge()).
 *  - La firma guardada de un usuario de la sede es personal y sirve en
 *    todas las sedes, por eso vive fuera del storage de la sede y se
 *    identifica por el correo (los ids de usuario no son estables entre
 *    la base central y las de cada sede).
 *
 * No hay columnas en base de datos: que exista el archivo es lo que
 * indica que hay firma.
 */
class ScheduleSignatureService
{
    /** Tope del data URL recibido (un trazo normal pesa decenas de KB). */
    private const MAX_DATA_URL_LENGTH = 2_000_000;

    /** Ancho maximo con el que se guarda la firma ya recortada. */
    private const MAX_WIDTH = 900;

    /** Margen transparente que se deja alrededor del trazo. */
    private const PADDING = 6;

    public function __construct(
        private readonly ScheduleEvidenceService $evidence,
    ) {}

    // ─── Firma de quien realizo la actividad ─────────────────────

    public function storePerformer(InventorySchedule $schedule, string $png): void
    {
        Storage::disk('local')->put($this->performerPath($schedule), $png);
    }

    public function performerFile(InventorySchedule $schedule): ?string
    {
        return $this->absolute($this->performerPath($schedule));
    }

    private function performerPath(InventorySchedule $schedule): string
    {
        return $this->evidence->directory($schedule).'/firma-realizada.png';
    }

    // ─── Firma guardada de un usuario de la sede ─────────────────

    public function hasSaved(User $user): bool
    {
        return $this->savedFile($user) !== null;
    }

    public function savedFile(User $user): ?string
    {
        return $this->absolute($this->userPath($user));
    }

    public function save(User $user, string $png): void
    {
        Storage::disk('local')->put($this->userPath($user), $png);
    }

    public function forget(User $user): void
    {
        Storage::disk('local')->delete($this->userPath($user));
    }

    /**
     * Firma guardada como data URL, para mostrarla en el modal.
     */
    public function savedDataUrl(User $user): ?string
    {
        $file = $this->savedFile($user);

        return $file ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($file)) : null;
    }

    private function userPath(User $user): string
    {
        return 'signatures/users/'.sha1(Str::lower(trim((string) $user->email))).'.png';
    }

    // ─── Validacion y limpieza ───────────────────────────────────

    /**
     * Convierte el data URL del lienzo en un PNG recortado al trazo.
     *
     * @throws ValidationException si no es una imagen PNG o esta en blanco
     */
    public function decode(?string $dataUrl, string $field): string
    {
        $fail = fn (string $message) => ValidationException::withMessages([$field => $message]);

        $dataUrl = (string) $dataUrl;

        if ($dataUrl === '' || strlen($dataUrl) > self::MAX_DATA_URL_LENGTH
            || ! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw $fail('La firma no es válida. Vuelve a firmar.');
        }

        $binary = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        $image = $binary === false ? false : @imagecreatefromstring($binary);

        if ($image === false) {
            throw $fail('La firma no es válida. Vuelve a firmar.');
        }

        $trimmed = $this->trim($image);
        imagedestroy($image);

        if ($trimmed === null) {
            throw $fail('La firma está en blanco. Firma dentro del recuadro.');
        }

        ob_start();
        imagepng($trimmed, null, 9);
        imagedestroy($trimmed);

        return (string) ob_get_clean();
    }

    /**
     * Recorta los bordes vacios y reduce el ancho. Devuelve null si el
     * lienzo no tiene trazo.
     *
     * @param  \GdImage  $image
     * @return \GdImage|null
     */
    private function trim($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $minX = $width;
        $minY = $height;
        $maxX = -1;
        $maxY = -1;

        // Se muestrea cada 2 px: basta para encontrar el trazo y es 4x mas rapido.
        for ($y = 0; $y < $height; $y += 2) {
            for ($x = 0; $x < $width; $x += 2) {
                $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;

                if ($alpha < 100) {
                    $minX = min($minX, $x);
                    $maxX = max($maxX, $x);
                    $minY = min($minY, $y);
                    $maxY = max($maxY, $y);
                }
            }
        }

        if ($maxX < 0) {
            return null;
        }

        $minX = max(0, $minX - self::PADDING);
        $minY = max(0, $minY - self::PADDING);
        $maxX = min($width - 1, $maxX + self::PADDING);
        $maxY = min($height - 1, $maxY + self::PADDING);

        $cropWidth = $maxX - $minX + 1;
        $cropHeight = $maxY - $minY + 1;
        $ratio = min(1, self::MAX_WIDTH / $cropWidth);
        $targetWidth = max(1, (int) round($cropWidth * $ratio));
        $targetHeight = max(1, (int) round($cropHeight * $ratio));

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $image, 0, 0, $minX, $minY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $target;
    }

    private function absolute(string $path): ?string
    {
        $disk = Storage::disk('local');

        return $disk->exists($path) ? $disk->path($path) : null;
    }
}
