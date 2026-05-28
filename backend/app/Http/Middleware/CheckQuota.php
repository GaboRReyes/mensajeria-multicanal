<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el usuario no haya superado su cuota mensual.
 * Se aplica a rutas que consumen mensajes (POST /messages, POST /campaigns/{id}/send).
 */
class CheckQuota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // Los admins nunca tienen límite
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Resetear cuota si cambió el mes
        if ($user->quota_reset_at && $user->quota_reset_at->isPast()) {
            $user->update([
                'used_this_month' => 0,
                'quota_reset_at'  => now()->addMonth()->startOfMonth(),
            ]);
        }

        if (! $user->hasQuota()) {
            return response()->json([
                'error'          => 'Has alcanzado tu límite mensual de mensajes.',
                'monthly_limit'  => $user->monthly_limit,
                'used_this_month'=> $user->used_this_month,
                'reset_at'       => $user->quota_reset_at,
            ], 429);
        }

        return $next($request);
    }
}
