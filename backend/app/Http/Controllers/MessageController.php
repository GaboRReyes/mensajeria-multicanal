<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'channel' => 'required|in:email,whatsapp',
            'to' => 'required|string',
            'template_id' => 'nullable|exists:templates,id',
            'variables' => 'nullable|array',
            'idempotency_key' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        // Idempotencia: si ya existe ese key, devolver el mensaje existente (spec 9)
        if (! empty($data['idempotency_key'])) {
            $existing = Message::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                return response()->json($existing, 200);
            }
        }

        // Provider activo del canal
        $provider = Provider::whereHas('channel', fn ($q) =>
            $q->where('type', $data['channel'])->where('is_active', true)
        )->where('is_active', true)->first();

        if (! $provider) {
            return response()->json(['error' => "No hay provider activo para el canal {$data['channel']}"], 422);
        }

        $isScheduled = ! empty($data['scheduled_at']) && now()->lt($data['scheduled_at']);

        $message = Message::create([
            'user_id' => $request->user()->id,
            'template_id' => $data['template_id'] ?? null,
            'provider_id' => $provider->id,
            'channel' => $data['channel'],
            'recipient_hash' => hash('sha256', $data['to']),
            'recipient_masked' => $this->mask($data['to']),
            'variables' => [
                'to' => $data['to'],
                'body' => $data['variables'] ?? [],
            ],
            'idempotency_key' => $data['idempotency_key'] ?? null,
            'status' => $isScheduled ? 'programado' : 'encolado',
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        // Despachar solo si no es programado
        if (! $isScheduled) {
            if ($data['channel'] === 'whatsapp') {
                SendWhatsAppMessageJob::dispatch($message->id);
            }
            // email: SendEmailJob lo añadimos después
        }

        return response()->json($message, 201);
    }

    public function show($uuid)
    {
        $message = Message::with('events')->findOrFail($uuid);
        return response()->json($message);
    }

    public function cancel($uuid)
    {
        $message = Message::findOrFail($uuid);

        if (! in_array($message->status, ['programado', 'encolado'])) {
            return response()->json([
                'error' => "No se puede cancelar un mensaje en estado {$message->status}",
            ], 422);
        }

        $message->update(['status' => 'cancelado']);
        return response()->json($message);
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