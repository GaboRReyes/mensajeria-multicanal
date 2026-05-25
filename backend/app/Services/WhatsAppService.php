<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private string $url;
    private string $token;

    public function __construct()
    {
        $version = config('services.whatsapp.api_version', 'v25.0');
        $phoneId = config('services.whatsapp.phone_number_id');
        $token   = config('services.whatsapp.access_token');

        // Validar antes de asignar: evita TypeError en PHP 8+ (typed property = null crash)
        if (empty($phoneId)) {
            throw new \InvalidArgumentException(
                'WhatsApp phone_number_id no configurado (WHATSAPP_PHONE_NUMBER_ID en .env).'
            );
        }

        if (empty($token)) {
            throw new \InvalidArgumentException(
                'WhatsApp access_token no configurado (WHATSAPP_ACCESS_TOKEN en .env).'
            );
        }

        $this->token = $token;
        $this->url   = "https://graph.facebook.com/{$version}/{$phoneId}/messages";
    }

    /**
     * Envía un mensaje basado en una plantilla aprobada por Meta.
     *
     * @param  string  $to            Número destino en formato internacional sin '+' (ej. 524613124690)
     * @param  string  $templateName  Nombre de la plantilla aprobada (ej. 'hello_world')
     * @param  string  $lang          Código de idioma (ej. 'en_US', 'es_MX')
     * @param  array   $variables     Variables del body de la plantilla
     * @return array                  Respuesta de la API de Meta (incluye el wamid)
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $lang = 'en_US',
        array $variables = []
    ): array {
        $template = [
            'name' => $templateName,
            'language' => ['code' => $lang],
        ];

        $components = $this->buildComponents($variables);
        if (! empty($components)) {
            $template['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ];

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->url, $payload);

        if ($response->failed()) {
            throw new \RuntimeException(
                'WhatsApp error (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Envía un mensaje de texto libre (solo válido dentro de la ventana de 24h
     * tras un mensaje entrante del usuario).
     */
    public function sendText(string $to, string $body): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ];

        $response = Http::withToken($this->token)
            ->acceptJson()
            ->post($this->url, $payload);

        if ($response->failed()) {
            throw new \RuntimeException(
                'WhatsApp error (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Construye el bloque 'components' con las variables del body.
     */
    private function buildComponents(array $vars): array
    {
        if (empty($vars)) {
            return [];
        }

        $params = array_map(
            fn ($v) => ['type' => 'text', 'text' => (string) $v],
            array_values($vars)
        );

        return [['type' => 'body', 'parameters' => $params]];
    }
}