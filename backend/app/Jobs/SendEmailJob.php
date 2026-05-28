<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageEvent;
use App\Services\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 1800];

    public function __construct(public string $messageId) {}

    public function handle(TemplateRenderer $renderer): void
    {
        $message = Message::with('template')->find($this->messageId);

        if (! $message) {
            Log::error('SendEmailJob: mensaje no encontrado', ['id' => $this->messageId]);
            return;
        }

        if ($message->status === Message::STATUS_CANCELLED) {
            return;
        }

        $message->update(['status' => Message::STATUS_PROCESSING]);
        $message->increment('attempts');

        try {
            $to       = $message->variables['to'] ?? null;
            $bodyVars = is_array($message->variables['body'] ?? null)
                        ? ($message->variables['body'] ?? [])
                        : [];

            if (! $to) {
                throw new \RuntimeException('No recipient (variables.to) provided');
            }

            $template = $message->template;

            // Asunto
            $subject = ! empty($bodyVars['subject'])
                ? $bodyVars['subject']
                : ($template ? $renderer->renderSubject($template, $bodyVars)
                             : 'Mensaje del Servicio Multicanal');

            // Cuerpo: texto libre > template renderizado
            if (! empty($bodyVars['text'])) {
                $body = $bodyVars['text'];
            } elseif ($template) {
                $body = $renderer->render($template, $bodyVars);
            } else {
                $body = '';
            }

            if (empty(trim($body))) {
                throw new \RuntimeException('El cuerpo del correo está vacío.');
            }

            Mail::raw($body, fn ($mail) => $mail->to($to)->subject($subject));

            $message->update([
                'status'  => Message::STATUS_SENT,
                'sent_at' => now(),
            ]);

            MessageEvent::create([
                'message_id'  => $message->id,
                'status'      => Message::STATUS_SENT,
                'occurred_at' => now(),
            ]);

            Log::info('SendEmailJob: enviado', ['id' => $message->id]);

            if ($message->campaign_id) {
                UpdateCampaignStatsJob::dispatch($message->campaign_id)->onQueue('low');
            }

        } catch (Throwable $e) {
            $message->update([
                'status'     => Message::STATUS_FAILED,
                'last_error' => ['message' => $e->getMessage()],
            ]);

            MessageEvent::create([
                'message_id'  => $message->id,
                'status'      => Message::STATUS_FAILED,
                'error'       => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            Log::error('SendEmailJob: error - ' . $e->getMessage());

            if ($message->campaign_id) {
                UpdateCampaignStatsJob::dispatch($message->campaign_id)->onQueue('low');
            }

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $message->update([
                'status'     => Message::STATUS_FAILED,
                'last_error' => ['message' => $e->getMessage(), 'at' => now()->toIso8601String()],
            ]);
        }
    }
}
