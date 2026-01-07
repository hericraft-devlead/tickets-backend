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
                'success' => false,
                'message' => 'No autorizado. Solo usuarios Moodle.',
                'debug' => [
                    'authenticated' => Auth::check(),
                    'guard_used' => 'moodle',
                    'user' => Auth::guard('moodle')->user() ? 'exists' : 'null'
                ]
            ], 401);
        }

        return $next($request);
    }
}