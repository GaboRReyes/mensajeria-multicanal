<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        return response()->json(['message' => 'Mensaje enviado']);
    }

    public function show($uuid)
    {
        return response()->json(['uuid' => $uuid]);
    }

    public function cancel($uuid)
    {
        return response()->json(['cancelled' => $uuid]);
    }
}