<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        $user = $request->user();

        // Check if user has the required active role
        if ($user->active_role !== $role) {
            return response()->json([
                'success' => false,
                'message' => "Access denied. {$role} role required.",
                'required_role' => $role,
                'current_role' => $user->active_role,
                'can_switch' => $user->canSwitchTo($role),
            ], 403);
        }

        // Check if user's profile for the role is complete (optional check)
        $profile = $user->getActiveProfile();
        if ($profile && !$profile->isComplete()) {
            return response()->json([
                'success' => false,
                'message' => "Please complete your {$role} profile to access this feature.",
                'profile_incomplete' => true,
                'profile_type' => $role,
            ], 400);
        }

        return $next($request);
    }
}