<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\TenancyServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Bloquea rutas de registro si accidentalmente quedan expuestas.
        $middleware->prepend(\App\Http\Middleware\BlockRegistration::class);

        // Resolver tenant dentro de web (despues de iniciar sesion).
        $middleware->appendToGroup('web', \App\Http\Middleware\ResolveTenant::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\PreventHistoryCache::class);

        // Verificar acceso al tenant.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureTenantAccess::class);

        // Asegurar que ResolveTenant corra antes de Authenticate.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\ResolveTenant::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
