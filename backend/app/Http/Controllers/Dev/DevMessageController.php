<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Provider;
use App\Services\ChannelDispatcher;
use Illuminate\Http\Request;

/**
 * Endpoints REST para developers que usan API Keys.
 * Misma lógica que MessageController pero autenticado por API Key.
 */
class DevMessageController extends Controller
{
    public function __construct(private ChannelDispatcher $dispatcher) {}

    /**
     * Listar mensajes del usuario autenticado con API Key.
     */
    public function index(Request $request)
    {
        $messages = Message::forUser($request->user()->id)
            ->latest()
            ->paginate(50, [
                'id', 'channel', 'recipient_masked', 'status', 'attempts',
                'scheduled_at', 'sent_at', 'delivered_at', 'campaign_id', 'created_at',
            ]);

        return response()->json($messages);
    }

    /**
     * Enviar un mensaje individual via API Key.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $apiKey = $request->get('_api_key');

        // Verificar habilidad
        if ($apiKey && ! $apiKey->can('messages:write')) {
            return response()->json(['error' => 'API Key sin permiso para enviar mensajes.'], 403);
        }

        if (! $user->hasQuota()) {
            return response()->json([
                'error'         => 'Cuota mensual alcanzada.',
                'monthly_limit' => $user->monthly_limit,
            ], 429);
        }

        $data = $request->validate([
            'channel'         => 'required|in:email,whatsapp',
            'to'              => 'required|string',
            'template_id'     => 'nullable|exists:templates,id',
            'variables'       => 'nullable|array',
            'idempotency_key' => 'nullable|string',
            'scheduled_at'    => 'nullable|date',
        ]);

        // Idempotencia
        if (! empty($data['idempotency_key'])) {
            $existing = Message::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) return response()->json($existing, 200);
        }

        $provider = Provider::whereHas('channel', fn ($q) =>
            $q->where('type', $data['channel'])->where('is_active', true)
        )->where('is_active', true)->first();

        if (! $provider) {
            return response()->json(['error' => "No hay provider activo para {$data['channel']}."], 422);
        }

        $isScheduled = ! empty($data['scheduled_at']) && now()->lt($data['scheduled_at']);

        $message = Message::create([
            'user_id'          => $user->id,
            'template_id'      => $data['template_id'] ?? null,
            'provider_id'      => $provider->id,
            'channel'          => $data['channel'],
            'recipient_hash'   => hash('sha256', $data['to']),
            'recipient_masked' => $this->mask($data['to']),
            'variables'        => ['to' => $data['to'], 'body' => $data['variables'] ?? []],
            'idempotency_key'  => $data['idempotency_key'] ?? null,
            'status'           => $isScheduled ? Message::STATUS_SCHEDULED : Message::STATUS_QUEUED,
            'scheduled_at'     => $data['scheduled_at'] ?? null,
        ]);

        if (! $isScheduled) {
            $this->dispatcher->dispatch($message);
            $user->incrementUsage();
        }

        return response()->json($message, 201);
    }

    /**
     * Ver detalle + eventos de un mensaje.
     */
    public function show(Request $request, string $uuid)
    {
        $message = Message::forUser($request->user()->id)
            ->with('events')
            ->findOrFail($uuid);

        return response()->json($message);
    }

    /**
     * Logs de todos los mensajes del developer.
     */
    public function logs(Request $request)
    {
        $apiKey = $request->get('_api_key');

        if ($apiKey && ! $apiKey->can('messages:read')) {
            return response()->json(['error' => 'API Key sin permiso para leer logs.'], 403);
        }

        $messages = Message::forUser($request->user()->id)
            ->with('events')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->channel, fn ($q) => $q->where('channel', $request->channel))
            ->latest()
            ->paginate(100);

        return response()->json($messages);
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
