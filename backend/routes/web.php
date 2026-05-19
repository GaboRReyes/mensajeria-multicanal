<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/pixel/{uuid}', function (string $uuid) {
    Message::where('id', $uuid)->whereNull('read_at')
        ->update(['status' => 'leido', 'read_at' => now()]);
 
    return response(base64_decode(
        'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
    ), 200)->header('Content-Type', 'image/gif');
})->name('messages.pixel');