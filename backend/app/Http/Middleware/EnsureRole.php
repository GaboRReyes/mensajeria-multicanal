<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de rol.
 * Uso en routes: ->middleware('role:admin')
 *                ->middleware('role:admin,client')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['error' => 'Cuenta desactivada.'], 403);
        }

        if (! empty($roles) && ! in_array($user->role, $roles)) {
            return response()->json([
                'error' => 'No tienes permisos para realizar esta acción.',
                'required_role' => $roles,
                'your_role'     => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
