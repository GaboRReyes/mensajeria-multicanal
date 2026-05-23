<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Message;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function index()
{
    $messages = Message::query()

        ->latest()

        ->take(50)

        ->get([
            'id',
            'channel',
            'recipient_masked',
            'status',
            'attempts',
            'scheduled_at',
            'sent_at',
            'delivered_at',
            'read_at',
            'created_at',
        ]);

    return response()->json($messages);
}
    public function store(Request $request)
    {
        $data = $request->validate([
            'recipient' => 'required|string|max:255',

            'content' => 'required|string',

            'channel' => 'required|in:email,whatsapp,both',

            'scheduled_at' => 'nullable|date',
        ]);

        try {

            /*
            |--------------------------------------------------------------------------
            | AUTH USER
            |--------------------------------------------------------------------------
            */

            $user = auth()->user();

            if (!$user) {

                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | PROVIDER
            |--------------------------------------------------------------------------
            */

            $provider = DB::table('providers')->first();

            if (!$provider) {

                return response()->json([
                    'message' => 'No existe provider configurado'
                ], 500);
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE MESSAGE
            |--------------------------------------------------------------------------
            */

            $message = Message::create([

                'user_id' => $user->id,

                'provider_id' => $provider->id,

                'topic' => 'Mensaje desde dashboard',

                'extension' => 'txt',

                'payload' => [
                    'content' => $data['content']
                ],

                'channel' => $data['channel'] === 'both'
                    ? 'email'
                    : $data['channel'],

                'recipient' => $data['recipient'],

                'recipient_hash' => hash(
                    'sha256',
                    $data['recipient']
                ),

                'recipient_masked' => $this->maskRecipient(
                    $data['recipient']
                ),

                'variables' => [
                    'recipient' => $data['recipient'],
                ],

                'status' => $request->filled('scheduled_at')
                    ? 'programado'
                    : 'encolado',

                'attempts' => 0,

                'scheduled_at' => $data['scheduled_at'] ?? null,

                'inserted_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | DISPATCH JOB
            |--------------------------------------------------------------------------
            */

            if (
                $request->filled('scheduled_at')
            ) {

                SendEmailJob::dispatch(
                    $message->id
                )->delay(
                    $data['scheduled_at']
                );

            } else {

                SendEmailJob::dispatch(
                    $message->id
                );
            }

            return response()->json([

                'success' => true,

                'message' => $request->filled('scheduled_at')
                    ? 'Mensaje programado correctamente'
                    : 'Mensaje enviado correctamente',

                'data' => $message,
            ]);

        } catch (\Throwable $e) {

            Log::error($e);

            return response()->json([

                'success' => false,

                'message' => 'Error al procesar mensaje',

                'error' => $e->getMessage(),

            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        return response()->json(

            Message::findOrFail($id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

    public function cancel(string $id)
    {
        $message = Message::findOrFail($id);

        if ($message->status === 'enviado') {

            return response()->json([

                'message' => 'El mensaje ya fue enviado'
            ], 400);
        }

        $message->update([
            'status' => 'cancelado'
        ]);

        return response()->json([

            'success' => true,

            'message' => 'Mensaje cancelado',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | MASK RECIPIENT
    |--------------------------------------------------------------------------
    */

    private function maskRecipient(
        string $recipient
    ): string {

        if (
            filter_var(
                $recipient,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            [$name, $domain] = explode(
                '@',
                $recipient
            );

            return substr($name, 0, 1)
                . '***@'
                . $domain;
        }

        return substr($recipient, 0, 3)
            . '*****';
    }
}