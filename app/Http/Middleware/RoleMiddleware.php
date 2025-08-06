<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $roles)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = $request->user();
        $allowedRoles = explode('|', $roles);

        // Check if user is superuser (always allowed)
        if ($user->is_superuser) {
            return $next($request);
        }

        // Check if user has staff privileges
        if (in_array('staff', $allowedRoles) && $user->is_staff) {
            return $next($request);
        }

        // Check if user has admin role
        if (in_array('admin', $allowedRoles) && ($user->is_superuser || $user->is_staff)) {
            return $next($request);
        }

        return response()->json(['error' => 'Insufficient permissions'], 403);
    }
}
