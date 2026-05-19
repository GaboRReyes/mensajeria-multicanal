<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        Log::info('WhatsApp webhook payload received', $this->payload);

        // TODO: procesar statuses (sent → delivered → read → failed)
        // TODO: procesar mensajes entrantes (si los habilitas)
        // Estructura típica del payload:
        // entry[0].changes[0].value.statuses[0] → { id, status, timestamp, recipient_id }
    }
}
