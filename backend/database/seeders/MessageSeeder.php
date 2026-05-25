<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $provider = DB::table('providers')->first();
        $template = Template::first();

        if (!$user || !$provider) {
            $this->command->warn(
                'No hay usuarios o providers disponibles.'
            );

            return;
        }

        $messages = [
            [
                'channel' => 'email',
                'status'  => 'enviado',
                'variables' => [
                    'name' => 'Gabriel',
                    'date' => now()->format('Y-m-d'),
                ],
            ],
            [
                'channel' => 'email',
                'status'  => 'entregado',
                'variables' => [
                    'name' => 'Administrador',
                    'company' => 'Mensajería Multicanal',
                ],
            ],
            [
                'channel' => 'whatsapp',
                'status'  => 'leido',
                'variables' => [],
            ],
        ];

        foreach ($messages as $msg) {

            $data = [
                'id' => Str::uuid(),

                'user_id' => $user->id,

                'template_id' => $template?->id,

                'provider_id' => $provider->id,

                'channel' => $msg['channel'],

                'recipient_hash' => hash(
                    'sha256',
                    $user->email
                ),

                'recipient_masked' => 't***@example.com',

                'variables' => $msg['variables'],

                'idempotency_key' => Str::uuid(),

                'status' => $msg['status'],

                'provider_message_id' => 'msg_' . Str::random(10),

                'attempts' => 1,

                'sent_at' => now(),
            ];

            if (
                in_array(
                    $msg['status'],
                    ['entregado', 'leido']
                )
            ) {
                $data['delivered_at'] = now();
            }

            if ($msg['status'] === 'leido') {
                $data['read_at'] = now();
            }

            Message::create($data);
        }
    }
}