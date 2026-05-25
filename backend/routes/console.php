<?php

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use Illuminate\Support\Facades\Schedule;

// Promueve mensajes programados cuya hora ya llegó
Schedule::call(function () {
    Message::where('status', 'programado')
        ->where('scheduled_at', '<=', now())
        ->chunkById(100, function ($chunk) {
            $chunk->each(function ($m) {
                $m->update(['status' => 'encolado']);

                if ($m->channel === 'email') {
                    if (class_exists(\App\Jobs\SendEmailJob::class)) {
                        \App\Jobs\SendEmailJob::dispatch($m->id);
                    }
                } else {
                    SendWhatsAppMessageJob::dispatch($m->id);
                }
            });
        });
})->everyMinute();