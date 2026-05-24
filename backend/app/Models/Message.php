<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasUuids;

    // La PK es uuid (no autoincremental)
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'template_id', 'provider_id', 'channel',
        'recipient_hash', 'recipient_masked', 'variables',
        'idempotency_key', 'status', 'provider_message_id',
        'attempts', 'last_error',
        'scheduled_at', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'last_error' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class);
    }
}