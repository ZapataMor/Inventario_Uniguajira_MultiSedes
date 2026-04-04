<?php

namespace App\Http\Controllers;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;

/**
 * Portal central.
 *
 * Solo accesible por usuarios con global_role = super_administrador.
 * Muestra las sedes disponibles para acceso directo.
 */
class PortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard principal del portal con cards de acceso a sedes.
     */
    public function index(Request $request)
    {
        $tenants = Tenant::where('is_active', true)->with('branding')->get();

        return view('portal.index', compact('tenants'));
    }

    /**
     * Cambia al tenant seleccionado.
     */
    public function switchToTenant(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $user = $request->user();

        if (! $user->isGlobalAdmin()) {
            abort(403, 'Solo los super administradores pueden acceder a las sedes desde el portal.');
        }

        $request->session()->put('tenant_id', $tenant->id);

        $redirectPath = $this->sanitizeRedirectPath((string) $request->query('redirect', '/home'))
            ?? '/home';
        $inplace = $request->boolean('inplace');

        $primaryDomain = $tenant->primaryDomain();
        if ($primaryDomain && ! $inplace) {
            $scheme = $request->isSecure() ? 'https' : 'http';
            $port = $request->getPort();
            $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';
            return redirect("{$scheme}://{$primaryDomain}{$portSuffix}{$redirectPath}");
        }

        return redirect($redirectPath);
    }

    private function sanitizeRedirectPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (str_contains($path, '://') || str_starts_with($path, '//')) {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        if (str_contains($path, '..') || preg_match('/[\r\n]/', $path)) {
            return null;
        }

        return $path;
    }
}
