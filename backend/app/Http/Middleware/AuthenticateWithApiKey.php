<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica peticiones que usan una API Key tipo Bearer.
 *
 * Soporta dos formatos:
 *   Authorization: Bearer sk_live_xxxxxxxx
 *   X-API-Key: sk_live_xxxxxxxx
 *
 * Si el token empieza con "sk_" se trata como API Key; si no,
 * se delega al guard de Sanctum normal (el middleware puede
 * combinarse: auth:sanctum + este middleware no es necesario
 * cuando se usa Sanctum).
 *
 * Uso recomendado: middleware('api.key') en rutas /dev/*
 */
class AuthenticateWithApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json(['error' => 'API Key requerida.'], 401);
        }

        // Si no empieza con "sk_" podría ser un token de Sanctum
        // Intentamos buscar como API Key de todas formas
        $apiKey = ApiKey::findByPlain($token);

        if (! $apiKey) {
            return response()->json(['error' => 'API Key inválida o expirada.'], 401);
        }

        $user = $apiKey->user;

        if (! $user || ! $user->is_active) {
            return response()->json(['error' => 'Cuenta desactivada.'], 403);
        }

        // Inyectamos el usuario en el request igual que Sanctum
        auth()->setUser($user);
        $request->merge(['_api_key' => $apiKey]);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        // Header X-API-Key tiene precedencia
        $header = $request->header('X-API-Key');
        if ($header) return $header;

        // Authorization: Bearer sk_live_xxx
        $bearer = $request->bearerToken();
        if ($bearer && str_starts_with($bearer, 'sk_')) {
            return $bearer;
        }

        return null;
    }
}
