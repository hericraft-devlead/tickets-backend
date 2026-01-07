<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMoodleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        
        if (!Auth::guard('moodle')->check()) {
            return response()->json([
                'message' => 'No autorizado. Solo usuarios Moodle.'
            ], 401);
        }

        return $next($request);
    }
}