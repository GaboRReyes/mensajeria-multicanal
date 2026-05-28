<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'template_id', 'name', 'description',
        'channels', 'variables', 'status',
        'total_contacts', 'total_messages',
        'sent_count', 'delivered_count', 'failed_count',
        'scheduled_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'channels'       => 'array',
        'variables'      => 'array',
        'scheduled_at'   => 'datetime',
        'started_at'     => 'datetime',
        'completed_at'   => 'datetime',
    ];

    // ─── Estados ──────────────────────────────────────────────────────────────

    const STATUS_DRAFT      = 'draft';
    const STATUS_SCHEDULED  = 'scheduled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENDING    = 'sending';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_CANCELLED  = 'cancelled';

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSendable($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function canBeSent(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_PROCESSING,
        ]);
    }

    /** Número de mensajes que debería generar: contactos × canales */
    public function expectedMessageCount(): int
    {
        return $this->contacts()->count() * count($this->channels ?? []);
    }

    // ─── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'campaign_contacts')
            ->withPivot('variables')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
