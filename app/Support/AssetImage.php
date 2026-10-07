<?php

namespace App\Support;

/**
 * Resuelve la imagen que se muestra para un bien.
 *
 * Si el usuario subio una imagen, se sirve esa. Si no, se usa un icono
 * por defecto segun el tipo del bien (Cantidad o Serial).
 */
class AssetImage
{
    public const DEFAULTS = [
        'cantidad' => 'assets/defaults/goods/cantidad.svg',
        'serial' => 'assets/defaults/goods/serial.svg',
    ];

    public const FALLBACK = 'assets/defaults/goods/default.jpg';

    /**
     * URL publica para el <img> de un bien.
     */
    public static function url(?string $image, ?string $type): string
    {
        if (empty($image)) {
            return self::defaultUrl($type);
        }

        $params = ['path' => $image];

        if ($key = self::typeKey($type)) {
            $params['type'] = $key;
        }

        return route('assets.image', $params);
    }

    /**
     * URL del icono por defecto segun el tipo del bien.
     */
    public static function defaultUrl(?string $type): string
    {
        return asset(self::defaultRelativePath($type));
    }

    /**
     * Ruta absoluta en disco del icono por defecto (usada por AssetImageController).
     */
    public static function defaultPath(?string $type): string
    {
        return public_path(self::defaultRelativePath($type));
    }

    private static function defaultRelativePath(?string $type): string
    {
        $key = self::typeKey($type);

        return $key ? self::DEFAULTS[$key] : self::FALLBACK;
    }

    private static function typeKey(?string $type): ?string
    {
        $key = strtolower(trim((string) $type));

        return array_key_exists($key, self::DEFAULTS) ? $key : null;
    }
}
