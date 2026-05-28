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
use Throwable;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $messageId) {}

    // Backoff exponencial: 1min → 5min → 30min
    public function backoff(): array
    {
        return [60, 300, 1800];
    }

    public function handle(WhatsAppService $whatsapp): void
    {
        $message = Message::with('template')->findOrFail($this->messageId);

        if ($message->status === Message::STATUS_CANCELLED) {
            return;
        }

        $message->update(['status' => Message::STATUS_PROCESSING]);
        $message->increment('attempts');

        $template     = $message->template;
        $to           = $message->variables['to'] ?? null;
        $freeText     = $message->variables['text'] ?? null;
        $bodyVars     = $message->variables['body'] ?? [];

        if (! $to) {
            $this->markFailed($message, 'No recipient (variables.to) provided');
            return;
        }

        if (! $template && $freeText) {
            $response = $whatsapp->sendText($to, $freeText);
        } else {
            $templateName = $template?->whatsapp_template_name ?? 'hello_world';
            $lang         = $template?->language ?? 'en_US';
            $expectedVars = $template?->variables ?? [];
            $vars         = ! empty($expectedVars) ? $bodyVars : [];
            $response     = $whatsapp->sendTemplate($to, $templateName, $lang, $vars);
        }

        $wamid = $response['messages'][0]['id'] ?? null;

        $message->update([
            'status'              => Message::STATUS_SENT,
            'provider_message_id' => $wamid,
            'sent_at'             => now(),
        ]);

        MessageEvent::create([
            'message_id'  => $message->id,
            'status'      => Message::STATUS_SENT,
            'payload'     => $response,
            'occurred_at' => now(),
        ]);

        // Actualizar estadísticas de campaña si aplica
        if ($message->campaign_id) {
            UpdateCampaignStatsJob::dispatch($message->campaign_id)->onQueue('low');
        }
    }

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
            'status'     => Message::STATUS_FAILED,
            'last_error' => ['message' => $error, 'at' => now()->toIso8601String()],
        ]);

        MessageEvent::create([
            'message_id'  => $message->id,
            'status'      => Message::STATUS_FAILED,
            'error'       => $error,
            'occurred_at' => now(),
        ]);

        if ($message->campaign_id) {
            UpdateCampaignStatsJob::dispatch($message->campaign_id)->onQueue('low');
        }
    }
}
