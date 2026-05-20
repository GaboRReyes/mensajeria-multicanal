<?php

use Illuminate\Support\Facades\Route;
use App\Models\Message;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pixel/{uuid}', function (string $uuid) {

    Message::where('uuid', $uuid)
        ->whereNull('read_at')
        ->update([
            'status' => 'leido',
            'read_at' => now()
        ]);

    return response(
        base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
        200
    )->header('Content-Type', 'image/gif');

})->name('messages.pixel');

use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {

    Mail::raw('Correo de prueba desde Resend', function ($message) {
        $message->to('22030785@itcelaya.edu.mx')
                ->subject('Prueba Resend');
    });

    return 'Correo enviado';
});