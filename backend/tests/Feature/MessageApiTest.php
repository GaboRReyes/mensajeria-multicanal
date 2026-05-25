<?php

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Channel;
use App\Models\Provider;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;



it('crea un mensaje de whatsapp y encola el job', function () {
    Queue::fake();

    // Datos base
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['type' => 'whatsapp']);
    Provider::factory()->create(['channel_id' => $channel->id, 'is_active' => true]);
    $template = Template::factory()->whatsapp()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/messages', [
            'channel' => 'whatsapp',
            'to' => '524613124690',
            'template_id' => $template->id,
            'variables' => [],
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'status', 'channel']);

    expect($response->json('status'))->toBe('encolado');

    Queue::assertPushed(SendWhatsAppMessageJob::class);
});

it('respeta la idempotencia', function () {
    Queue::fake();

    $user = User::factory()->create();
    $channel = Channel::factory()->create(['type' => 'whatsapp']);
    Provider::factory()->create(['channel_id' => $channel->id, 'is_active' => true]);
    $template = Template::factory()->whatsapp()->create();

    $payload = [
        'channel' => 'whatsapp',
        'to' => '524613124690',
        'template_id' => $template->id,
        'idempotency_key' => 'clave-unica-123',
    ];

    $first = $this->actingAs($user, 'sanctum')->postJson('/api/v1/messages', $payload);
    $second = $this->actingAs($user, 'sanctum')->postJson('/api/v1/messages', $payload);

    // El segundo debe devolver el mismo mensaje (no crear otro)
    expect($first->json('id'))->toBe($second->json('id'));
});

it('rechaza sin autenticación', function () {
    $this->postJson('/api/v1/messages', [
        'channel' => 'whatsapp',
        'to' => '524613124690',
    ])->assertStatus(401);
});