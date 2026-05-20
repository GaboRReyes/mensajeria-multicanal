<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [

        'uuid',
        'channel',
        'status',
        'recipient_masked',

        'template_id',

        'sent_at',
        'delivered_at',
        'read_at',

    ];
}