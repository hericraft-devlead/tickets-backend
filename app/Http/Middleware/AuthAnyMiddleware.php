<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthAnyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('sanctum')->check() && !Auth::guard('moodle')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere autenticación.'
            ], 401);
        }
        
        return $next($request);
    }
}
