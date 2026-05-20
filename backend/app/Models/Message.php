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
        'topic',           // ← agregar
        'extension',       // ← agregar
        'channel',
        'recipient_hash',
        'recipient_masked', // ← agregar
        'status',
        'attempts',        // ← agregar
        'inserted_at',     // ← agregar
        'sent_at',
        'delivered_at',
        'read_at',
    ];
}