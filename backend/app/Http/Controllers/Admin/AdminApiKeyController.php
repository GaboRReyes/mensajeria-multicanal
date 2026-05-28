<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;

class AdminApiKeyController extends Controller
{
    /** Lista todas las API keys de un usuario */
    public function index(int $userId)
    {
        $user = User::findOrFail($userId);

        return response()->json(
            $user->apiKeys()->orderByDesc('created_at')->get([
                'id', 'name', 'prefix', 'abilities', 'is_active', 'last_used_at', 'expires_at', 'created_at',
            ])
        );
    }

    /** Genera una nueva API key para un usuario */
    public function store(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'abilities'  => 'nullable|array',
            'env'        => 'nullable|in:live,test',
            'expires_at' => 'nullable|date|after:now',
        ]);

        ['model' => $apiKey, 'plain' => $plain] = ApiKey::generate(
            userId   : $user->id,
            name     : $data['name'],
            abilities: $data['abilities'] ?? ['*'],
            env      : $data['env'] ?? 'live',
            expiresAt: isset($data['expires_at']) ? \Carbon\Carbon::parse($data['expires_at']) : null,
        );

        return response()->json([
            'api_key' => $apiKey,
            'token'   => $plain, // ← solo se muestra UNA vez
            'warning' => 'Guarda este token ahora. No podrás verlo nuevamente.',
        ], 201);
    }

    /** Revoca (desactiva) una API key */
    public function revoke(int $userId, int $keyId)
    {
        $apiKey = ApiKey::where('user_id', $userId)->findOrFail($keyId);
        $apiKey->update(['is_active' => false]);
        return response()->json(['revoked' => true]);
    }

    /** Elimina permanentemente */
    public function destroy(int $userId, int $keyId)
    {
        $apiKey = ApiKey::where('user_id', $userId)->findOrFail($keyId);
        $apiKey->delete();
        return response()->json(['deleted' => true]);
    }
}
