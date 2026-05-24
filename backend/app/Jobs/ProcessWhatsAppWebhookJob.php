<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $entries = $this->payload['entry'] ?? [];

        foreach ($entries as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                // Procesar cambios de estado (sent/delivered/read/failed)
                foreach ($value['statuses'] ?? [] as $status) {
                    $this->handleStatus($status);
                }
            }
        }
    }

    private function handleStatus(array $status): void
    {
        $wamid = $status['id'] ?? null;
        $metaStatus = $status['status'] ?? null;   // sent | delivered | read | failed

        if (! $wamid || ! $metaStatus) {
            return;
        }

        $message = Message::where('provider_message_id', $wamid)->first();

        if (! $message) {
            Log::info('Webhook status para wamid desconocido', ['wamid' => $wamid]);
            return;
        }

        // Mapear estado de Meta → estado interno
        $map = [
            'sent'      => 'enviado',
            'delivered' => 'entregado',
            'read'      => 'leido',
            'failed'    => 'fallido',
        ];

        $internalStatus = $map[$metaStatus] ?? null;
        if (! $internalStatus) {
            return;
        }

        // No retroceder estados (si ya está 'leido', no volver a 'entregado')
        $order = ['encolado' => 0, 'enviado' => 1, 'entregado' => 2, 'leido' => 3];
        $current = $order[$message->status] ?? -1;
        $incoming = $order[$internalStatus] ?? 99;

        if ($internalStatus !== 'fallido' && $incoming < $current) {
            return; // llegó un status viejo, ignorar
        }

        // Actualizar el mensaje
        $updates = ['status' => $internalStatus];
        $ts = isset($status['timestamp']) ? Carbon::createFromTimestamp($status['timestamp']) : now();

        match ($metaStatus) {
            'delivered' => $updates['delivered_at'] = $ts,
            'read'      => $updates['read_at'] = $ts,
            default     => null,
        };

        $message->update($updates);

        // Registrar evento de auditoría
        MessageEvent::create([
            'message_id'  => $message->id,
            'status'      => $internalStatus,
            'payload'     => $status,
            'occurred_at' => $ts,
        ]);
    }
}