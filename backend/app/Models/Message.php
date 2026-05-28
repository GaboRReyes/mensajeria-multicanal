<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasUuids;

    protected $table = 'messages';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'campaign_id', 'contact_id',
        'template_id', 'provider_id', 'channel',
        'recipient_hash', 'recipient_masked', 'variables',
        'idempotency_key', 'status', 'provider_message_id',
        'attempts', 'last_error',
        'scheduled_at', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected $casts = [
        'variables'    => 'array',
        'last_error'   => 'array',
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // ─── Estados ──────────────────────────────────────────────────────────────

    const STATUS_QUEUED      = 'encolado';
    const STATUS_PROCESSING  = 'procesando';
    const STATUS_SENT        = 'enviado';
    const STATUS_DELIVERED   = 'entregado';
    const STATUS_READ        = 'leido';
    const STATUS_FAILED      = 'fallido';
    const STATUS_CANCELLED   = 'cancelado';
    const STATUS_SCHEDULED   = 'programado';

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCampaign($query, string $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function template(): BelongsTo { return $this->belongsTo(Template::class); }
    public function provider(): BelongsTo { return $this->belongsTo(Provider::class); }
    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function contact(): BelongsTo  { return $this->belongsTo(Contact::class); }

    public function events(): HasMany
    {
        return $this->hasMany(MessageEvent::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_SCHEDULED]);
    }
}
