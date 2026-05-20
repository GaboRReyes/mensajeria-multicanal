<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

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
    'recipient_hash',
    'private',
    'recipient_masked',
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
}