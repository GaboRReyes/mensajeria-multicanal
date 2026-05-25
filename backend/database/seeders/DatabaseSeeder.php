<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Provider;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
     

        

        $whatsapp = Channel::create([
            'name' => 'WhatsApp Tec',
            'type' => 'whatsapp',
            'is_active' => true,
        ]);
        $email = Channel::create([
            'name' => 'Email',
            'type' => 'email',
            'is_active' => true,
        ]);
        Provider::create([
            'channel_id' => $whatsapp->id,
            'name' => 'Meta Cloud API',
            'driver' => 'meta_cloud',
            'config' => ['note' => 'Credenciales en .env (WHATSAPP_*)'],
            'is_active' => true,
        ]);

          // Ejecutar seeders individuales
        $this->call([
            ProviderSeeder::class,
            UserSeeder::class,
            TemplateSeeder::class,
            MessageSeeder::class,
        ]);

        $this->command->info('Seed completo: usuario, canales, providers y plantillas.');
    }
}