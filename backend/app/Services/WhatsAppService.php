class WhatsAppService
{
    private string $url;
    private string $token;
 
    public function __construct()
    {
        $version = config('services.whatsapp.api_version', 'v20.0');
        $phoneId = config('services.whatsapp.phone_id');
        if (empty($phoneId)) {
        $this->token = config('services.whatsapp.token');
        if (empty($this->token)) {
            throw new \InvalidArgumentException("WhatsApp token is not set in configuration (services.whatsapp.token).");
        }
        }
        $this->url   = "https://graph.facebook.com/{$version}/{$phoneId}/messages";
        $this->token = config('services.whatsapp.token');
    }
 
        $template = [
            'name' => $templateName,
            'language' => ['code' => $lang],
        ];
        $components = $this->buildComponents($variables);
        if (!empty($components)) {
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
            'WhatsApp error (HTTP '.$response->status().'): '.$response->body()
        );
    }
    return $response->json();
}
            return [];
        }
 
    private function buildComponents(array $vars): array
    {
        if (empty($vars)) return [];
        $params = array_map(
            fn($v) => ['type' => 'text', 'text' => (string) $v],
            array_values($vars)
        );
        return [['type' => 'body', 'parameters' => $params]];
    }
}
