<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('providers')->insert([
            [
                'name'       => 'Resend',
                'channel'    => 'email',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Twilio',
                'channel'    => 'whatsapp',
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}