<?php

use Illuminate\Support\Facades\Route;

// Controllers — Auth
use App\Http\Controllers\AuthController;

// Controllers — Compartidos (Sanctum)
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ReportController;

// Controllers — Admin
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\GlobalStatsController;
use App\Http\Controllers\Admin\AdminApiKeyController;

// Controllers — Client
use App\Http\Controllers\Client\CampaignController;
use App\Http\Controllers\Client\ContactController;

// Controllers — Developer (API Key)
use App\Http\Controllers\Dev\DevApiKeyController;
use App\Http\Controllers\Dev\DevMessageController;

// Controllers — Webhooks
use App\Http\Controllers\Api\V1\Webhooks\WhatsAppWebhookController;

// ═══════════════════════════════════════════════════════════════════════════════
// HEALTH CHECK  (público)
// ═══════════════════════════════════════════════════════════════════════════════
Route::get('/v1/health', fn () => response()->json([
    'status'    => 'ok',
    'service'   => 'mensajeria-multicanal',
    'timestamp' => now()->toIso8601String(),
]));

// ═══════════════════════════════════════════════════════════════════════════════
// AUTENTICACIÓN  (público)
// ═══════════════════════════════════════════════════════════════════════════════
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/token', [AuthController::class, 'token']);
});

// Alias antiguo — backward compatible
Route::post('/v1/login', [AuthController::class, 'login']);
Route::post('/v1/auth/token', [AuthController::class, 'token']);

// ═══════════════════════════════════════════════════════════════════════════════
// WEBHOOKS  (público — Meta los llama sin Auth)
// ═══════════════════════════════════════════════════════════════════════════════
Route::prefix('v1/webhooks')->group(function () {
    Route::get('whatsapp',  [WhatsAppWebhookController::class, 'verify']);
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle']);
});

// ═══════════════════════════════════════════════════════════════════════════════
// API PROTEGIDA CON SANCTUM  (web dashboard — cliente y admin)
// ═══════════════════════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    // ── MENSAJES INDIVIDUALES ──────────────────────────────────────────────────
    Route::get('/messages',         [MessageController::class, 'index']);
    Route::post('/messages',        [MessageController::class, 'store']);
    Route::get('/messages/{uuid}',  [MessageController::class, 'show']);
    Route::delete('/messages/{uuid}', [MessageController::class, 'cancel']);

    // ── TEMPLATES ─────────────────────────────────────────────────────────────
    Route::apiResource('/templates', TemplateController::class);

    // ── REPORTES ──────────────────────────────────────────────────────────────
    Route::get('/reports/kpis',            [ReportController::class, 'kpis']);
    Route::get('/reports/export/{format}', [ReportController::class, 'export']);

    // ── CAMPAÑAS  (client + admin) ────────────────────────────────────────────
    Route::prefix('campaigns')->group(function () {
        Route::get('/',                          [CampaignController::class, 'index']);
        Route::post('/',                         [CampaignController::class, 'store']);
        Route::get('/{uuid}',                    [CampaignController::class, 'show']);
        Route::put('/{uuid}',                    [CampaignController::class, 'update']);
        Route::delete('/{uuid}',                 [CampaignController::class, 'destroy']);
        Route::post('/{uuid}/send',              [CampaignController::class, 'send']);
        Route::post('/{uuid}/contacts',          [CampaignController::class, 'addContacts']);
        Route::get('/{uuid}/stats',              [CampaignController::class, 'stats']);
    });

    // ── CONTACTOS  (client + admin) ───────────────────────────────────────────
    Route::prefix('contacts')->group(function () {
        Route::get('/',             [ContactController::class, 'index']);
        Route::post('/',            [ContactController::class, 'store']);
        Route::post('/import',      [ContactController::class, 'import']);
        Route::get('/{uuid}',       [ContactController::class, 'show']);
        Route::put('/{uuid}',       [ContactController::class, 'update']);
        Route::delete('/{uuid}',    [ContactController::class, 'destroy']);
    });

    // ── DEVELOPER: API Keys propias (autenticado con Sanctum) ─────────────────
    Route::prefix('dev')->middleware('role:developer,admin')->group(function () {
        Route::get('/api-keys',            [DevApiKeyController::class, 'index']);
        Route::post('/api-keys',           [DevApiKeyController::class, 'store']);
        Route::delete('/api-keys/{id}',    [DevApiKeyController::class, 'destroy']);
        Route::patch('/api-keys/{id}/revoke', [DevApiKeyController::class, 'revoke']);
    });

    // ── ADMIN ─────────────────────────────────────────────────────────────────
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Usuarios
        Route::get('/users',                    [UserController::class, 'index']);
        Route::post('/users',                   [UserController::class, 'store']);
        Route::get('/users/{id}',               [UserController::class, 'show']);
        Route::put('/users/{id}',               [UserController::class, 'update']);
        Route::delete('/users/{id}',            [UserController::class, 'destroy']);
        Route::patch('/users/{id}/toggle',      [UserController::class, 'toggleActive']);

        // API Keys de cualquier usuario
        Route::get('/users/{userId}/api-keys',               [AdminApiKeyController::class, 'index']);
        Route::post('/users/{userId}/api-keys',              [AdminApiKeyController::class, 'store']);
        Route::patch('/users/{userId}/api-keys/{keyId}/revoke', [AdminApiKeyController::class, 'revoke']);
        Route::delete('/users/{userId}/api-keys/{keyId}',    [AdminApiKeyController::class, 'destroy']);

        // Estadísticas globales y logs
        Route::get('/stats', [GlobalStatsController::class, 'index']);
        Route::get('/logs',  [GlobalStatsController::class, 'logs']);
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// API PROTEGIDA CON API KEY  (developer — REST sin sesión)
// ═══════════════════════════════════════════════════════════════════════════════
Route::middleware('api.key')->prefix('v1/api')->group(function () {

    // Mensajes
    Route::get('/messages',          [DevMessageController::class, 'index']);
    Route::post('/messages',         [DevMessageController::class, 'store']);
    Route::get('/messages/{uuid}',   [DevMessageController::class, 'show']);
    Route::get('/logs',              [DevMessageController::class, 'logs']);
});
