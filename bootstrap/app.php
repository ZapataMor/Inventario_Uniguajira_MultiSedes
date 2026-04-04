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
        // bloquea rutas de registro si accidentalmente quedan expuestas
        $middleware->prepend(\App\Http\Middleware\BlockRegistration::class);

        // Resolver tenant al inicio de cada request web
        $middleware->append(\App\Http\Middleware\ResolveTenant::class);

        // Verificar acceso del usuario al tenant después de autenticación
        $middleware->appendToGroup('web', \App\Http\Middleware\EnsureTenantAccess::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
