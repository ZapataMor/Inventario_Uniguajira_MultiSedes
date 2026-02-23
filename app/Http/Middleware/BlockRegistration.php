<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockRegistration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('register') || $request->is('register/*')) {
            // Registration is disabled: return 404 or redirect to login
            abort(404);
        }

        return $next($request);
    }
}
