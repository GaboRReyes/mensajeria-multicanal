<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\ReportController;

Route::post('/v1/auth/token', [AuthController::class, 'token']);
Route::post('/v1/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/{uuid}', [MessageController::class, 'show']);
    Route::delete('/messages/{uuid}', [MessageController::class, 'cancel']);

    Route::apiResource('/templates', TemplateController::class);

    Route::get('/reports/kpis', [ReportController::class, 'kpis']);
    Route::get('/reports/export/{format}', [ReportController::class, 'export']);
});

Route::prefix('v1')->group(function () {

    Route::get('/reports/kpis', [ReportController::class, 'kpis']);

    Route::get('/reports/export/{format}', [
        ReportController::class,
        'export'
    ]);

});