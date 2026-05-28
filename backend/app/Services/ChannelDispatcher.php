<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Message;

/**
 * Despacha el Job correcto según el canal del mensaje.
 * Centraliza la lógica de enrutamiento canal → Job.
 *
 * Para añadir un canal nuevo:
 *   1. Crear el Job en App\Jobs\Send{Canal}Job
 *   2. Registrar el mapeo en $channelJobMap
 */
class ChannelDispatcher
{
    /** Canal → clase del Job */
    private array $channelJobMap = [
        'whatsapp' => SendWhatsAppMessageJob::class,
        'email'    => SendEmailJob::class,
    ];

    /**
     * Encola el mensaje en el Job apropiado.
     * Si el canal no está soportado lanza una excepción.
     */
    public function dispatch(Message $message): void
    {
        $channel = $message->channel;

        if (! isset($this->channelJobMap[$channel])) {
            throw new \InvalidArgumentException(
                "Canal '{$channel}' no soportado. Canales disponibles: " .
                implode(', ', array_keys($this->channelJobMap))
            );
        }

        $jobClass = $this->channelJobMap[$channel];
        $jobClass::dispatch($message->id);
    }

    /**
     * Despacha múltiples mensajes.
     */
    public function dispatchMany(iterable $messages): void
    {
        foreach ($messages as $message) {
            $this->dispatch($message);
        }
    }

    /**
     * Devuelve los canales soportados.
     */
    public function supportedChannels(): array
    {
        return array_keys($this->channelJobMap);
    }
}
