<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Message extends Model
{
    use HasUuids;

    protected $table = 'messages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'user_id',
        'template_id',
        'provider_id',

        'topic',
        'extension',

        'payload',

        'channel',

        'event',

        'recipient',

        'recipient_hash',

        'recipient_masked',

        'private',

        'variables',

        'idempotency_key',

        'status',

        'provider_message_id',

        'attempts',

        'last_error',

        'scheduled_at',

        'inserted_at',

        'sent_at',

        'delivered_at',

        'read_at',
    ];

    protected $casts = [

        'payload' => 'array',

        'variables' => 'array',

        'last_error' => 'array',

        'private' => 'boolean',

        'scheduled_at' => 'datetime',

        'inserted_at' => 'datetime',

        'sent_at' => 'datetime',

        'delivered_at' => 'datetime',

        'read_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}