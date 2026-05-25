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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 1800];

    public string $messageId;

    public function __construct(string $messageId)
    {
        $this->messageId = $messageId;
    }

    public function handle(): void
    {
        $message = Message::with('template')->find($this->messageId);

        if (! $message) {
            Log::error('SendEmailJob: mensaje no encontrado', ['id' => $this->messageId]);
            return;
        }

        if ($message->status === 'cancelado') {
            return;
        }

        $message->increment('attempts');

        try {
            // Destinatario: ahora vive en variables['to'] (no en recipient)
            $to = $message->variables['to'] ?? null;
            if (! $to) {
                throw new \RuntimeException('No recipient (variables.to) provided');
            }

            $template = $message->template;
            $subject  = $template?->subject ?? 'Mensaje del Servicio Multicanal';
            $body     = $template?->body ?? '';

            // Reemplazo simple de variables {{clave}} con los valores enviados
            $vars = $message->variables['body'] ?? [];
            foreach ($vars as $key => $value) {
                $body = str_replace('{{' . $key . '}}', (string) $value, $body);
            }

            Mail::raw($body, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject);
            });

            $message->update([
                'status'  => 'enviado',
                'sent_at' => now(),
            ]);

            // Registrar evento (auditoría, igual que WhatsApp)
            \App\Models\MessageEvent::create([
                'message_id'  => $message->id,
                'status'      => 'enviado',
                'occurred_at' => now(),
            ]);

            Log::info('SendEmailJob: email enviado', ['id' => $message->id]);

        } catch (\Throwable $e) {
            $message->update([
                'status'     => 'fallido',
                'last_error' => ['message' => $e->getMessage()],
            ]);

            \App\Models\MessageEvent::create([
                'message_id'  => $message->id,
                'status'      => 'fallido',
                'error'       => $e->getMessage(),
                'occurred_at' => now(),
            ]);

            Log::error('SendEmailJob: error - ' . $e->getMessage());
            throw $e;
        }
    }
}