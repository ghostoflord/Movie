<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RbacMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // SUPER_ADMIN có hết
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return $next($request);
        }

        $method = strtoupper($request->method());
        $path = ltrim($request->path(), '/'); // ex: api/admin/dashboard

        if (method_exists($user, 'hasPermission') && $user->hasPermission($method, $path)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden',
            'details' => [
                'method' => $method,
                'path' => $path,
            ],
        ], 403);
    }
}

