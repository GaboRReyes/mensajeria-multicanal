<?php

use Illuminate\Support\Facades\Schedule;

use App\Models\Message;

use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsAppJob;

Schedule::call(function () {

    Message::where('status', 'programado')
        ->where('scheduled_at', '<=', now())
        ->chunkById(100, function ($messages) {

            foreach ($messages as $message) {

                if ($message->channel === 'email') {

                    SendEmailJob::dispatch($message->id);

                } else {

                    SendWhatsAppJob::dispatch($message->id);

                }

                $message->update([
                    'status' => 'en_cola'
                ]);
            }
        });

})->everyMinute();