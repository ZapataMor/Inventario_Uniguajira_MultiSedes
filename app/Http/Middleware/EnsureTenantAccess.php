<?php

namespace App\Http\Middleware;

use App\Models\Central\UserTenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario autenticado tiene acceso al tenant activo.
 *
 * Un super_administrador tiene acceso al portal y a todos los tenants.
 * Los demas usuarios solo acceden si tienen una membresia activa
 * en el tenant resuelto.
 */
class EnsureTenantAccess
{
    public function __construct(
        protected TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = $this->context->get();

        // Si no hay usuario autenticado, dejar que auth lo maneje.
        if (! $user) {
            return $next($request);
        }

        // Portal central: acceso exclusivo para super administradores.
        if (! $tenant) {
            if ($request->routeIs('logout')) {
                return $next($request);
            }

            if (! $this->isGlobalAdmin($user)) {
                abort(403, 'Solo los super administradores pueden acceder al portal central.');
            }

            return $next($request);
        }

        // Permitir acceso a rutas del portal central.
        if ($request->routeIs('portal.*')) {
            return $next($request);
        }

        // Permitir rutas de autenticacion (login, logout, etc.).
        if ($request->routeIs('login', 'logout', 'register', 'password.*', 'verification.*', 'two-factor.*')) {
            return $next($request);
        }

        // Permitir rutas de Fortify por path.
        $authPaths = ['login', 'logout', 'register', 'forgot-password', 'reset-password', 'email/verify', 'user/two-factor', 'two-factor-challenge'];
        foreach ($authPaths as $authPath) {
            if ($request->is($authPath.'*')) {
                return $next($request);
            }
        }

        // Admin general tiene acceso a todos los tenants.
        if ($this->isGlobalAdmin($user)) {
            return $next($request);
        }

        // Evitar que una sesion iniciada en otra sede se reutilice aqui.
        $sessionTenantId = $request->session()->get('auth_tenant_id');
        if ($sessionTenantId && (int) $sessionTenantId !== (int) $tenant->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Tu sesion pertenecia a otra sede. Inicia sesion nuevamente.');
        }

        // Verificar membresia del usuario en el tenant activo.
        $membership = UserTenant::where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->first();

        if (! $membership) {
            $tenantRoles = config('tenancy.tenant_roles', ['administrador', 'consultor']);

            if (! in_array($user->role, $tenantRoles, true)) {
                abort(403, 'No tienes acceso a esta sede.');
            }

            $request->attributes->set('tenant_role', $user->role);
            $request->session()->put('auth_tenant_id', (int) $tenant->id);

            return $next($request);
        }

        // Inyectar rol en el request y fijar sede de sesion autenticada.
        $request->attributes->set('tenant_role', $membership->role);
        $request->session()->put('auth_tenant_id', (int) $tenant->id);

        return $next($request);
    }

    protected function isGlobalAdmin($user): bool
    {
        if (method_exists($user, 'isGlobalAdmin')) {
            return $user->isGlobalAdmin();
        }

        $globalRoles = config('tenancy.global_roles', ['super_administrador']);

        return in_array($user->global_role, $globalRoles)
            || in_array($user->email, ['admin@example.edu.co'], true);
    }
}
