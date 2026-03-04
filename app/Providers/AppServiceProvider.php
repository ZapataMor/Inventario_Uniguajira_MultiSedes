<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Helpers\ActivityLogger;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // ✅ Registrar evento de login
        Event::listen(Login::class, function (Login $event) {
            if ($event->user) {
                // Actualizar last_login_at
                $event->user->update([
                    'last_login_at' => now(),
                ]);
                
                // Log de actividad
                ActivityLogger::login($event->user->username ?? $event->user->email);
            }
        });

        // ✅ Registrar evento de logout
        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                ActivityLogger::logout($event->user->username ?? $event->user->email);
            }
        });
    }
}