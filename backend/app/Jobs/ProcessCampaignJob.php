<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use App\Models\Provider;
use App\Services\ChannelDispatcher;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job principal para procesar una campaña masiva.
 *
 * Flujo:
 *   1. Marca la campaña como 'processing'
 *   2. Por cada contacto × canal genera un Message individual
 *   3. Despacha los Jobs de envío (SendWhatsAppMessageJob / SendEmailJob)
 *   4. Marca la campaña como 'sending'
 *
 * Si falla → marca la campaña como 'failed'.
 */
class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 1; // Si falla el procesamiento no reintentamos (es idempotente)
    public int $timeout = 300; // 5 minutos para campañas grandes

    public function __construct(public string $campaignId) {}

    public function handle(ChannelDispatcher $dispatcher): void
    {
        $campaign = Campaign::with(['contacts', 'template', 'user'])->find($this->campaignId);

        if (! $campaign) {
            Log::error('ProcessCampaignJob: campaña no encontrada', ['id' => $this->campaignId]);
            return;
        }

        if (! $campaign->canBeSent()) {
            Log::warning('ProcessCampaignJob: campaña no enviable', [
                'id'     => $this->campaignId,
                'status' => $campaign->status,
            ]);
            return;
        }

        // ── 1. Marcar como procesando ─────────────────────────────────────
        $campaign->update([
            'status'     => Campaign::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        try {
            $channels  = $campaign->channels;
            $contacts  = $campaign->contacts;
            $globalVars= $campaign->variables ?? [];
            $template  = $campaign->template;

            $totalMessages = 0;
            $messages      = [];

            // ── 2. Generar Messages (contacto × canal) ───────────────────
            DB::transaction(function () use (
                $campaign, $channels, $contacts, $globalVars, $template, &$messages, &$totalMessages
            ) {
                foreach ($contacts as $contact) {
                    // Variables: globales + por contacto (las del pivote sobreescriben)
                    $contactVars = array_merge(
                        $globalVars,
                        $contact->pivot->variables ?? [],
                        // Metadatos del contacto disponibles como vars: {{nombre}}, {{email}}
                        [
                            'nombre' => $contact->name,
                            'email'  => $contact->email ?? '',
                            'phone'  => $contact->phone ?? '',
                        ]
                    );

                    foreach ($channels as $channel) {
                        // Validar que el contacto tenga el canal requerido
                        if ($channel === 'email' && ! $contact->hasEmail()) continue;
                        if ($channel === 'whatsapp' && ! $contact->hasPhone()) continue;

                        $provider = Provider::whereHas('channel', fn ($q) =>
                            $q->where('type', $channel)->where('is_active', true)
                        )->where('is_active', true)->first();

                        if (! $provider) continue;

                        $recipient = $channel === 'email' ? $contact->email : $contact->phone;

                        $message = Message::create([
                            'user_id'          => $campaign->user_id,
                            'campaign_id'      => $campaign->id,
                            'contact_id'       => $contact->id,
                            'template_id'      => $template?->id,
                            'provider_id'      => $provider->id,
                            'channel'          => $channel,
                            'recipient_hash'   => hash('sha256', $recipient),
                            'recipient_masked' => $this->mask($recipient),
                            'variables'        => [
                                'to'   => $recipient,
                                'body' => $contactVars,
                            ],
                            'status'           => Message::STATUS_QUEUED,
                        ]);

                        $messages[] = $message;
                        $totalMessages++;
                    }
                }

                // Actualizar contadores
                $campaign->update([
                    'total_contacts' => $contacts->count(),
                    'total_messages' => $totalMessages,
                ]);
            });

            // ── 3. Despachar Jobs de envío ────────────────────────────────
            foreach ($messages as $message) {
                $dispatcher->dispatch($message);
            }

            // ── 4. Marcar como sending ────────────────────────────────────
            $campaign->update(['status' => Campaign::STATUS_SENDING]);

            // Incrementar uso del usuario
            $campaign->user->incrementUsage($totalMessages);

            Log::info('ProcessCampaignJob: campaña procesada', [
                'campaign_id'   => $campaign->id,
                'total_messages'=> $totalMessages,
            ]);

        } catch (Throwable $e) {
            $campaign->update(['status' => Campaign::STATUS_FAILED]);

            Log::error('ProcessCampaignJob: error al procesar campaña', [
                'campaign_id' => $this->campaignId,
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function mask(string $recipient): string
    {
        if (str_contains($recipient, '@')) {
            [$local, $domain] = explode('@', $recipient, 2);
            return substr($local, 0, 1) . '***@' . $domain;
        }
        return substr($recipient, 0, 3) . '****' . substr($recipient, -2);
    }
}
