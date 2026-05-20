<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $providerId = \DB::table('providers')->first()->id;

        $messages = [
            [
                'channel' => 'email',
                'status'  => 'enviado',
                'topic'   => 'Bienvenida',
            ],
            [
                'channel' => 'email',
                'status'  => 'entregado',
                'topic'   => 'Confirmación de cuenta',
            ],
            [
                'channel' => 'whatsapp',
                'status'  => 'leido',
                'topic'   => 'Notificación de pago',
            ],
        ];

        foreach ($messages as $msg) {
            $user->messages()->create([
                'id'               => Str::uuid(),
                'provider_id'      => $providerId,
                'topic'            => $msg['topic'],
                'extension'        => 'html',
                'channel'          => $msg['channel'],
                'recipient_hash'   => hash('sha256', $user->email),
                'recipient_masked' => 't***@example.com',
                'status'           => $msg['status'],
                'attempts'         => 1,
                'inserted_at'      => now(),
                'sent_at'          => now(),
                'sent_at'      => now(),
                'delivered_at' => now(),
            ]);
        }
    }
}
