<?php

namespace App\Support\Tenancy;

use App\Models\Central\Domain;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;

/**
 * Resuelve el tenant activo basándose en la estrategia configurada:
 * - subdomain: maicao.inventario.uniguajira.edu.co
 * - domain: inventario-maicao.uniguajira.edu.co
 * - session: selección desde el portal central
 */
class TenantResolver
{
    /**
     * Resuelve el tenant desde el request actual.
     *
     * @return Tenant|null null si estamos en el portal central
     *
     * @throws \RuntimeException si no se puede resolver el tenant
     */
    public function resolve(Request $request): ?Tenant
    {
        if ($this->isPortalRoute($request) || $request->boolean('portal')) {
            $request->session()->forget('tenant_id');

            return null;
        }

        if ($tenant = $this->resolveByFullDomain($request->getHost(), $request, false)) {
            return $tenant;
        }

        if ($request->session()->has('tenant_id')) {
            return $this->resolveBySession($request);
        }

        $strategy = config('tenancy.resolution_strategy', 'subdomain');

        return match ($strategy) {
            'subdomain' => $this->resolveBySubdomain($request),
            'domain' => $this->resolveByDomain($request),
            'session' => $this->resolveBySession($request),
            default => throw new \RuntimeException("Estrategia de resolución de tenant no soportada: {$strategy}"),
        };
    }

    /**
     * Resuelve por subdominio.
     * maicao.base-domain.com → tenant 'maicao'
     */
    protected function resolveBySubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $baseDomain = config('tenancy.base_domain', 'localhost');

        // Si el host es exactamente el dominio base, estamos en portal central
        if ($host === $baseDomain) {
            return $this->resolveCentralOrDefault($request);
        }

        // Extraer subdominio
        $subdomain = str_replace('.'.$baseDomain, '', $host);

        if (empty($subdomain) || $subdomain === $host) {
            // No se encontró subdominio, intentar por dominio completo
            return $this->resolveByFullDomain($host, $request);
        }

        // Verificar si es el portal central
        $centralDomain = config('tenancy.central_domain');
        if ($centralDomain && $this->hostsMatch($host, $centralDomain)) {
            return null; // Portal central
        }

        return $this->findTenantByDomainOrSlug($subdomain, $host);
    }

    /**
     * Resuelve por dominio completo registrado en la tabla domains.
     */
    protected function resolveByDomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        return $this->resolveByFullDomain($host, $request);
    }

    /**
     * Resuelve desde la sesión (después de selección en portal central).
     */
    protected function resolveBySession(Request $request): ?Tenant
    {
        $tenantId = $request->session()->get('tenant_id');

        if (! $tenantId) {
            return $this->resolveCentralOrDefault($request);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            return $this->resolveCentralOrDefault($request);
        }

        return $tenant;
    }

    /**
     * Busca tenant por dominio completo en la tabla domains, o por slug.
     */
    protected function findTenantByDomainOrSlug(string $slug, string $host): ?Tenant
    {
        // Primero buscar por dominio registrado
        $domain = Domain::where('domain', $host)
            ->where('is_active', true)
            ->with('tenant')
            ->first();

        if ($domain && $domain->tenant->is_active) {
            return $domain->tenant;
        }

        // Si no hay dominio, buscar por slug
        $tenant = Tenant::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        return $tenant;
    }

    /**
     * Busca un tenant por el dominio completo del host.
     */
    protected function resolveByFullDomain(string $host, Request $request, bool $allowFallback = true): ?Tenant
    {
        $domain = Domain::where('domain', $host)
            ->where('is_active', true)
            ->with('tenant')
            ->first();

        if ($domain && $domain->tenant->is_active) {
            return $domain->tenant;
        }

        return $allowFallback
            ? $this->resolveCentralOrDefault($request)
            : null;
    }

    /**
     * Si no se resuelve tenant, devuelve el default o null (portal central).
     */
    protected function resolveCentralOrDefault(Request $request): ?Tenant
    {
        $centralDomain = config('tenancy.central_domain');

        // Si hay un dominio central configurado y el host coincide
        if ($centralDomain && $this->hostsMatch($request->getHost(), $centralDomain)) {
            return null; // Portal central
        }

        // En desarrollo local o cuando no hay subdominio, usar el tenant por defecto
        $defaultSlug = config('tenancy.default_tenant');

        if ($defaultSlug) {
            return Tenant::where('slug', $defaultSlug)
                ->where('is_active', true)
                ->first();
        }

        return null;
    }

    protected function hostsMatch(string $left, string $right): bool
    {
        return $this->normalizeHost($left) === $this->normalizeHost($right);
    }

    protected function normalizeHost(string $host): string
    {
        return rtrim(mb_strtolower(trim($host)), '.');
    }

    protected function isPortalRoute(Request $request): bool
    {
        return $request->routeIs('portal.*')
            || $request->is('portal')
            || $request->is('portal/*');
    }
}
