<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el tenant activo al inicio de cada request HTTP.
 *
 * Este middleware debe ejecutarse antes de cualquier lógica de negocio
 * que acceda a datos operativos del tenant.
 */
class ResolveTenant
{
    public function __construct(
        protected TenantResolver $resolver,
        protected TenantContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Si ya se resolvió en este request, no resolver de nuevo
        if ($this->context->isResolved()) {
            return $next($request);
        }

        $tenant = $this->resolver->resolve($request);

        if ($tenant) {
            $this->context->set($tenant);

            // Compartir datos de branding con todas las vistas
            $branding = $tenant->branding;
            view()->share('tenant', $tenant);
            view()->share('branding', $branding);
        } else {
            // Portal central: marcar como resuelto sin tenant
            view()->share('tenant', null);
            view()->share('branding', null);
        }

        return $next($request);
    }
}
