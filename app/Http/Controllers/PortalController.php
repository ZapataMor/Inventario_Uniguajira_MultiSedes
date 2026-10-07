<?php

namespace App\Http\Controllers;

use App\Models\Central\Tenant;
use App\Support\Tenancy\PortalHandoff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $this->middleware('auth')->except('enterFromPortal');
    }

    /**
     * Dashboard principal del portal con cards de acceso a sedes.
     */
    public function index(Request $request)
    {
        $tenants = Tenant::where('is_active', true)->with('branding')->get();

        if ($request->ajax()) {
            /** @var \Illuminate\View\View $view */
            $view = view('portal.index', compact('tenants'));

            return $view->renderSections()['content'];
        }

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

        $redirectPath = $this->sanitizeRedirectPath((string) $request->query('redirect', '/home'))
            ?? '/home';
        $inplace = $request->boolean('inplace');

        // La sede tiene dominio propio: se entra alli con una sesion de sede, no con la del portal.
        $primaryDomain = $tenant->primaryDomain();
        if ($primaryDomain && ! $inplace) {
            $scheme = $request->isSecure() ? 'https' : 'http';
            $port = $request->getPort();
            $portSuffix = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : '';
            $token = app(PortalHandoff::class)->issue($user, $tenant, $redirectPath);

            return redirect("{$scheme}://{$primaryDomain}{$portSuffix}".route('sede.portal-access', ['token' => $token], false));
        }

        $request->session()->put('tenant_id', $tenant->id);

        if ($inplace) {
            $redirectPath = $this->appendQueryParameter($redirectPath, 'from_portal', '1');
        }

        return redirect($redirectPath);
    }

    /**
     * Canjea en la sede el token emitido por switchToTenant y abre la sesion del
     * super administrador con su usuario de esa sede.
     */
    public function enterFromPortal(Request $request, PortalHandoff $handoff)
    {
        $tenant = tenant();

        if (! $tenant) {
            return redirect()->route('portal.index');
        }

        $result = $handoff->redeem((string) $request->query('token', ''), $tenant);

        if (! $result) {
            return redirect()->route('login')
                ->with('status', 'El acceso desde el portal expiro. Vuelve a abrir la sede desde el portal o inicia sesion.');
        }

        Auth::guard('web')->login($result['user']);
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $tenant->id);
        $request->session()->put('auth_tenant_id', $tenant->id);

        return redirect($this->sanitizeRedirectPath($result['redirect']) ?? '/home');
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
            $path = '/'.ltrim($path, '/');
        }

        if (str_contains($path, '..') || preg_match('/[\r\n]/', $path)) {
            return null;
        }

        return $path;
    }

    private function appendQueryParameter(string $path, string $key, string $value): string
    {
        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.urlencode($key).'='.urlencode($value);
    }
}
