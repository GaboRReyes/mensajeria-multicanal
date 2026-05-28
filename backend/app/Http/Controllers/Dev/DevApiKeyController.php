<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;

/**
 * Controlador para que los desarrolladores gestionen sus propias API keys.
 * Solo accesible si el usuario tiene role = developer (o admin).
 */
class DevApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $keys = $request->user()->apiKeys()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'prefix', 'abilities', 'is_active', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json($keys);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'abilities'  => 'nullable|array',
            'abilities.*'=> 'string',
            'env'        => 'nullable|in:live,test',
            'expires_at' => 'nullable|date|after:now',
        ]);

        // Limitar a 10 API keys por usuario no-admin
        if (! $user->isAdmin() && $user->apiKeys()->where('is_active', true)->count() >= 10) {
            return response()->json([
                'error' => 'Has alcanzado el límite de 10 API Keys activas.',
            ], 422);
        }

        ['model' => $apiKey, 'plain' => $plain] = ApiKey::generate(
            userId   : $user->id,
            name     : $data['name'],
            abilities: $data['abilities'] ?? ['messages:write', 'messages:read'],
            env      : $data['env'] ?? 'live',
            expiresAt: isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
        );

        return response()->json([
            'api_key' => [
                'id'         => $apiKey->id,
                'name'       => $apiKey->name,
                'prefix'     => $apiKey->prefix,
                'abilities'  => $apiKey->abilities,
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
            'token'   => $plain,
            'warning' => 'Guarda este token ahora. No podrás verlo nuevamente.',
        ], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)->findOrFail($id);
        $apiKey->delete();
        return response()->json(['deleted' => true]);
    }

    public function revoke(Request $request, int $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)->findOrFail($id);
        $apiKey->update(['is_active' => false]);
        return response()->json(['revoked' => true]);
    }
}
