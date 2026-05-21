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

        Mail::raw('Hola desde Laravel', function ($mail) {

            $mail->to('reyesjosafat816@gmail.com')
                ->subject('Prueba');
        });

        $message->update([
            'status' => 'enviado'
        ]);

        Log::info('EMAIL ENVIADO');

    } catch (\Throwable $e) {

        Log::error('ERROR EN JOB: ' . $e->getMessage());

        throw $e;
    }
}
    public function failed(Throwable $e): void
    {
        $message = Message::find($this->messageId);

        if ($message) {

            $message->update([
                'status' => 'fallido'
            ]);
        }

        Log::error('JOB FALLÓ DEFINITIVAMENTE');

        Log::error($e->getMessage());
    }
}