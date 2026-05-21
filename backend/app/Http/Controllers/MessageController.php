<?php

namespace App\Http\Controllers;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendEmailJob;

class MessageController extends Controller
{
    public function store(Request $request){
        $providerId = DB::table('providers')->first()->id;
$message = Message::create([
    'id'               => Str::uuid(),
    'user_id'          => auth()->id(),
    'provider_id'      => $providerId,
    'channel'          => $request->channel,
    'recipient_hash'   => hash('sha256', $request->recipient),
    'recipient_masked' => 't***@gmail.com',
    'idempotency_key'  => $request->idempotency_key,
    'status'           => 'encolado',
]);

SendEmailJob::dispatch($message->id);

return response()->json([
    'message' => 'Mensaje encolado',
    'data' => $message
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