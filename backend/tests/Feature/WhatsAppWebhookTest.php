<?php

use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Provider;
use App\Models\User;

function mensajeEnviado(string $wamid): Message
{
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['type' => 'whatsapp']);
    $provider = Provider::factory()->create(['channel_id' => $channel->id]);

    return Message::create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'channel' => 'whatsapp',
        'recipient_hash' => hash('sha256', '524613124690'),
        'recipient_masked' => '524****90',
        'variables' => ['to' => '524613124690'],
        'status' => 'enviado',
        'provider_message_id' => $wamid,
    ]);
}

function payloadStatus(string $wamid, string $status): array
{
    return [
        'entry' => [[
            'changes' => [[
                'value' => [
                    'statuses' => [[
                        'id' => $wamid,
                        'status' => $status,
                        'timestamp' => (string) now()->timestamp,
                    ]],
                ],
            ]],
        ]],
    ];
}

it('actualiza a entregado cuando llega status delivered', function () {
    $message = mensajeEnviado('wamid.ABC');

    (new ProcessWhatsAppWebhookJob(payloadStatus('wamid.ABC', 'delivered')))->handle();

    $message->refresh();
    expect($message->status)->toBe('entregado');
    expect($message->delivered_at)->not->toBeNull();
});

it('actualiza a leido cuando llega status read', function () {
    $message = mensajeEnviado('wamid.DEF');

    (new ProcessWhatsAppWebhookJob(payloadStatus('wamid.DEF', 'read')))->handle();

    $message->refresh();
    expect($message->status)->toBe('leido');
    expect($message->read_at)->not->toBeNull();
});

it('no retrocede de leido a entregado', function () {
    $message = mensajeEnviado('wamid.GHI');
    $message->update(['status' => 'leido']);

    (new ProcessWhatsAppWebhookJob(payloadStatus('wamid.GHI', 'delivered')))->handle();

    $message->refresh();
    expect($message->status)->toBe('leido'); // se mantiene
});

it('ignora status de wamid desconocido', function () {
    $message = mensajeEnviado('wamid.REAL');

    (new ProcessWhatsAppWebhookJob(payloadStatus('wamid.NOEXISTE', 'delivered')))->handle();

    $message->refresh();
    expect($message->status)->toBe('enviado'); // no cambió
});