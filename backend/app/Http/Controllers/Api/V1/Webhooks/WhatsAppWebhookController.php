<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * GET — verificación inicial del webhook por parte de Meta.
     * Meta envía: ?hub.mode=subscribe&hub.verify_token=XXX&hub.challenge=YYY
     * Debemos responder con el challenge en texto plano si el token coincide.
     */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode'  => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * POST — eventos reales (statuses y mensajes entrantes).
     * Validamos la firma X-Hub-Signature-256 y encolamos el procesamiento.
     */
    public function handle(Request $request)
    {
        // 1) Validar firma (seguridad)
        if (! $this->isValidSignature($request)) {
            Log::warning('WhatsApp webhook signature invalid');
            return response('Invalid signature', 403);
        }

        // 2) Encolar procesamiento — respondemos 200 lo más rápido posible
        //    porque Meta tiene timeout corto y reintenta si no respondemos a tiempo
        ProcessWhatsAppWebhookJob::dispatch($request->all());

        return response('EVENT_RECEIVED', 200);
    }

    /**
     * Valida la firma HMAC-SHA256 que Meta envía en X-Hub-Signature-256.
     */
    private function isValidSignature(Request $request): bool
    {
        // En entorno local, permitir webhooks de prueba sin firma
        // (los POST de "Probar" de Meta y curls de desarrollo no vienen firmados)
        if (app()->environment('local')) {
            return true;
        }

        $signature = $request->header('X-Hub-Signature-256');
        $secret    = config('services.whatsapp.app_secret');

        if (! $signature || ! $secret) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}