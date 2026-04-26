<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure API routes behave like API: no HTML redirects, always JSON negotiation.
        if (! $request->headers->has('Accept')) {
            $request->headers->set('Accept', 'application/json');
        }

        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $next($request);
    }
}

