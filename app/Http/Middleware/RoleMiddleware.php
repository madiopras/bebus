<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. User not authenticated.',
            ], 401);
        }

        $user = auth()->user();

        if (empty($roles)) {
            return response()->json([
                'status' => false,
                'message' => 'No role specified for middleware.',
            ], 400); // Bad Request
        }

        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Required role(s): ' . implode(', ', $roles),
                'user_roles' => $user->getRoleNames(), // Debugging info
            ], 403);
        }

        return $next($request);
    }
}
