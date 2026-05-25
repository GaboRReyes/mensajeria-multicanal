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
                'channel_id' => 2 ,
                'is_active'     => true,
                'created_at' => now(),
                'driver' => 'meta_cloud',
                'updated_at' => now(),
            ],
            [
                'name'       => 'Twilio',
                'channel_id' => 1 ,
                'is_active'     => true,
                'driver' => 'twilio',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}