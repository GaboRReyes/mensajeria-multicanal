<?php

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Channel;
use App\Models\Message;
use App\Models\Provider;
use App\Models\Template;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function crearMensaje(): Message
{
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['type' => 'whatsapp']);
    $provider = Provider::factory()->create(['channel_id' => $channel->id]);
    $template = Template::factory()->whatsapp()->create();

    return Message::create([
        'user_id' => $user->id,
        'template_id' => $template->id,
        'provider_id' => $provider->id,
        'channel' => 'whatsapp',
        'recipient_hash' => hash('sha256', '524613124690'),
        'recipient_masked' => '524****90',
        'variables' => ['to' => '524613124690', 'body' => []],
        'status' => 'encolado',
    ]);
}

it('marca el mensaje como enviado cuando Meta responde ok', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'messages' => [['id' => 'wamid.TEST123']],
        ], 200),
    ]);

    $message = crearMensaje();

    SendWhatsAppMessageJob::dispatchSync($message->id);

    $message->refresh();
    expect($message->status)->toBe('enviado');
    expect($message->provider_message_id)->toBe('wamid.TEST123');
    expect($message->events()->count())->toBe(1);
});

it('marca el mensaje como fallido cuando Meta devuelve error', function () {
    Http::fake([
        'graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Auth error', 'code' => 190],
        ], 401),
    ]);

    $message = crearMensaje();

    // dispatchSync lanza la excepción; la capturamos para inspeccionar el estado
    try {
        SendWhatsAppMessageJob::dispatchSync($message->id);
    } catch (\Throwable $e) {
        // esperado: el job lanza RuntimeException con el error de Meta
    }

    $message->refresh();
    expect($message->status)->toBe('fallido');
    expect($message->attempts)->toBe(1);
});

it('no envía si el mensaje fue cancelado', function () {
    Http::fake();

    $message = crearMensaje();
    $message->update(['status' => 'cancelado']);

    SendWhatsAppMessageJob::dispatchSync($message->id);

    Http::assertNothingSent();
    $message->refresh();
    expect($message->status)->toBe('cancelado');
});