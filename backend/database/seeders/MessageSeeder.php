<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $provider = DB::table('providers')->first();

        if (!$user || !$provider) {
            $this->command->warn('No hay usuarios o providers disponibles.');
            return;
        }

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

            $data = [
                'id'               => Str::uuid(),
                'provider_id'      => $provider->id,
                'topic'            => $msg['topic'],
                'extension'        => 'html',
                'channel'          => $msg['channel'],
                'recipient_hash'   => hash('sha256', $user->email),
                'recipient_masked' => 't***@example.com',
                'status'           => $msg['status'],
                'attempts'         => 1,
                'inserted_at'      => now(),
                'sent_at'          => now(),
            ];

            if ($msg['status'] === 'entregado' || $msg['status'] === 'leido') {
                $data['delivered_at'] = now();
            }

            if ($msg['status'] === 'leido') {
                $data['read_at'] = now();
            }

            $user->messages()->create($data);
        }
    }
}