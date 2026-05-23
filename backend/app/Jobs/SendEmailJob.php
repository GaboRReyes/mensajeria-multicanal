<?php

namespace App\Jobs;

use Throwable;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public int $tries = 3;

    public array $backoff = [
        60,
        300,
        1800
    ];

    public string $messageId;

    public function __construct(string $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
{
    Log::info('JOB INICIADO');

    $message = Message::find($this->messageId);

    if (!$message) {

        Log::error('Mensaje no encontrado');
        return;
    }

    try {

        $payload = $message->payload ?? [];

        $content = is_array($payload)
            ? ($payload['content'] ?? '')
            : json_decode($payload, true)['content'] ?? '';


        Mail::raw(
            $content,
            function ($mail) use ($message) {

                $mail->to($message->recipient)
                    ->subject($message->topic);
            }
        );

        $message->update([

            'status' => 'enviado',

            'sent_at' => now(),

            'attempts' => $message->attempts + 1,
        ]);

        Log::info('EMAIL ENVIADO');

    } catch (\Throwable $e) {

        $message->update([

            'status' => 'fallido',

            'last_error' => [
                'message' => $e->getMessage()
            ],
        ]);

        Log::error(
            'ERROR EN JOB: '
            . $e->getMessage()
        );

        throw $e;
    }
}
}