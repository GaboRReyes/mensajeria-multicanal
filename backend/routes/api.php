<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ReportController;

Route::post('/v1/auth/token', [AuthController::class, 'token']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{uuid}', [MessageController::class, 'show']);
    Route::delete('/messages/{uuid}', [MessageController::class, 'cancel']);

    Route::apiResource('/templates', TemplateController::class);

    Route::get('/reports/kpis', [ReportController::class, 'kpis']);
    Route::get('/reports/export/{format}', [ReportController::class, 'export']);
});
Route::get('/v1/whatsapp/webhook', function (Request $r) {
    if ($r->query('hub_verify_token') === config('services.whatsapp.verify_token')) {
        return response($r->query('hub_challenge'), 200);
    }
    return response('', 403);
});
 
Route::post('/v1/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);
use App\Http\Controllers\Api\V1\Webhooks\WhatsAppWebhookController;

Route::prefix('v1/webhooks')->group(function () {
    Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify']);
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle']);
});
