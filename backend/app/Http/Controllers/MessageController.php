<?php

namespace App\Http\Controllers;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient' => 'required|string|max:255',
            'content' => 'required|string',
            'channel' => 'required|in:whatsapp,email,both',
        ]);

        $sentChannels = [];

        if (in_array($data['channel'], ['email', 'both'], true)) {
            Mail::raw($data['content'], function ($message) use ($data) {
                $message->to($data['recipient'])
                    ->subject('Mensaje desde el panel');
            });

            $sentChannels[] = 'email';
        }

        if (in_array($data['channel'], ['whatsapp', 'both'], true)) {
            // Aquí puedes integrar tu servicio de WhatsApp real.
            // Por ahora devolvemos una respuesta simulada de envío.
            $sentChannels[] = 'whatsapp';
        }

        return response()->json([
            'success' => true,
            'recipient' => $data['recipient'],
            'channel' => $data['channel'],
            'sent_channels' => $sentChannels,
            'message' => 'Mensaje procesado correctamente',
        ]);
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