<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Super-Admin-Token');
        $expectedToken = env('SUPER_ADMIN_TOKEN', 'Admin@FaithCore');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'message' => 'Unauthorized Super Admin access. Invalid passcode or token.'
            ], 401);
        }

        return $next($request);
    }
}
