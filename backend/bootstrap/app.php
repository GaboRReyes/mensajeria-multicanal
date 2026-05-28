<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
	api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'api/v1/webhooks/whatsapp',
        ]);

        // Alias de middleware para uso en rutas
        $middleware->alias([
            'role'    => \App\Http\Middleware\EnsureRole::class,
            'api.key' => \App\Http\Middleware\AuthenticateWithApiKey::class,
            'quota'   => \App\Http\Middleware\CheckQuota::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
