<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $messageId) {}

    // Backoff exponencial: 1min, 5min, 30min (spec 4.3)
    public function backoff(): array
    {
        return [60, 300, 1800];
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $message = Message::with('template')->findOrFail($this->messageId);

        // Si fue cancelado mientras esperaba, no enviar
        if ($message->status === 'cancelado') {
            return;
        }

        $message->increment('attempts');

        $template = $message->template;
        $templateName = $template?->whatsapp_template_name ?? 'hello_world';
        $lang = $template?->language ?? 'en_US';

        // El número real va en variables['to'] (no se guarda en claro por privacidad)
        $to = $message->variables['to'] ?? null;
        if (! $to) {
            $this->markFailed($message, 'No recipient (variables.to) provided');
            return;
        }

        $bodyVars = $message->variables['body'] ?? [];

        $response = $whatsapp->sendTemplate($to, $templateName, $lang, $bodyVars);

        $wamid = $response['messages'][0]['id'] ?? null;

        $message->update([
            'status' => 'enviado',
            'provider_message_id' => $wamid,
            'sent_at' => now(),
        ]);

        MessageEvent::create([
            'message_id' => $message->id,
            'status' => 'enviado',
            'payload' => $response,
            'occurred_at' => now(),
        ]);
    }

    // Se llama cuando se agotan los reintentos
    public function failed(Throwable $e): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $this->markFailed($message, $e->getMessage());
        }
    }

    private function markFailed(Message $message, string $error): void
    {
        $message->update([
            'status' => 'fallido',
            'last_error' => ['message' => $error, 'at' => now()->toIso8601String()],
        ]);

        MessageEvent::create([
            'message_id' => $message->id,
            'status' => 'fallido',
            'error' => $error,
            'occurred_at' => now(),
        ]);
    }
}