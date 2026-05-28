<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'prefix',
        'hashed_key',
        'abilities',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities'    => 'array',
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['hashed_key'];

    // ─── Generación ───────────────────────────────────────────────────────────

    /**
     * Genera una nueva API key.
     * Retorna ['model' => ApiKey, 'plain' => 'sk_live_xxxxx']
     */
    public static function generate(
        int $userId,
        string $name,
        array $abilities = ['*'],
        ?string $env = 'live',
        ?\Carbon\Carbon $expiresAt = null
    ): array {
        $random    = Str::random(40);
        $plain     = "sk_{$env}_{$random}";
        $prefix    = substr($plain, 0, 16); // "sk_live_xxxxxxxx"
        $hashed    = hash('sha256', $plain);

        $model = static::create([
            'user_id'    => $userId,
            'name'       => $name,
            'prefix'     => $prefix,
            'hashed_key' => $hashed,
            'abilities'  => $abilities,
            'is_active'  => true,
            'expires_at' => $expiresAt,
        ]);

        return ['model' => $model, 'plain' => $plain];
    }

    /**
     * Busca y valida una API key en texto plano.
     * Retorna el modelo si es válida y activa, null si no.
     */
    public static function findByPlain(string $plain): ?static
    {
        $hashed = hash('sha256', $plain);

        $key = static::where('hashed_key', $hashed)
            ->where('is_active', true)
            ->first();

        if (! $key) return null;

        if ($key->expires_at && $key->expires_at->isPast()) return null;

        $key->update(['last_used_at' => now()]);

        return $key;
    }

    // ─── Capacidades ──────────────────────────────────────────────────────────

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];
        return in_array('*', $abilities) || in_array($ability, $abilities);
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
