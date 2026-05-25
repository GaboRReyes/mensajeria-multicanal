<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Models\Message;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pixel/{uuid}', function (string $uuid) {
    Message::where('id', $uuid)
        ->whereNull('read_at')
        ->update([
            'status' => 'leido',
            'read_at' => now(),
        ]);

    return response(
        base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
        200
    )->header('Content-Type', 'image/gif');
})->name('messages.pixel');

Route::get('/test-mail', function () {
    Mail::raw('Correo de prueba desde Brevo', function ($message) {
        $message->to('reyesjosafat816@gmail.com')
                ->subject('Prueba Laravel + Brevo');
    });

    return 'Correo enviado';
});