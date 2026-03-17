<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetImageController extends Controller
{
    public function show(Request $request, string $path): BinaryFileResponse
    {
        $fullPath = $this->resolveImagePath($path);

        if ($fullPath === null || ! is_file($fullPath)) {
            return response()->file(public_path('assets/defaults/goods/default.jpg'));
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function resolveImagePath(string $path): ?string
    {
        $relativePath = ltrim($path, '/\\');

        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $basePath = str_starts_with($relativePath, 'seeders/')
            ? storage_path('app')
            : storage_path('app/public');

        $fullPath = realpath($basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if ($fullPath === false) {
            return null;
        }

        $expectedBase = realpath($basePath);

        if ($expectedBase === false || ! str_starts_with($fullPath, $expectedBase)) {
            return null;
        }

        return $fullPath;
    }
}
