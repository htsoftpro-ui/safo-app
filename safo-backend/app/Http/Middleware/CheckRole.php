<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Role-based access control middleware.
 *
 * Usage: ->middleware('role:supplier')
 *        ->middleware('role:customer,supplier')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !in_array($user->type, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول',
            ], 403);
        }

        return $next($request);
    }
}
