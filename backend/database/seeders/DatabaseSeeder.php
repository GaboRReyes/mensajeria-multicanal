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
        // Usuario integrador de prueba
        $user = User::create([
            'name' => 'Integrador Demo',
            'email' => 'demo@itcelaya.edu.mx',
            'password' => Hash::make('password'),
        ]);

        // Canal Email + su provider (Brevo SMTP)
        $email = Channel::create([
            'name' => 'Email Institucional',
            'type' => 'email',
            'is_active' => true,
        ]);
        Provider::create([
            'channel_id' => $email->id,
            'name' => 'Brevo SMTP',
            'driver' => 'smtp',
            'config' => ['note' => 'Credenciales reales en .env (MAIL_*)'],
            'is_active' => true,
        ]);

        // Canal WhatsApp + su provider (Meta Cloud API)
        $whatsapp = Channel::create([
            'name' => 'WhatsApp Tec',
            'type' => 'whatsapp',
            'is_active' => true,
        ]);
        Provider::create([
            'channel_id' => $whatsapp->id,
            'name' => 'Meta Cloud API',
            'driver' => 'meta_cloud',
            'config' => ['note' => 'Credenciales reales en .env (WHATSAPP_*)'],
            'is_active' => true,
        ]);

        // Plantilla WhatsApp de prueba (la que Meta da por defecto)
        Template::create([
            'name' => 'Hola Mundo (WhatsApp)',
            'whatsapp_template_name' => 'hello_world',
            'channel' => 'whatsapp',
            'body' => 'Plantilla de bienvenida de prueba de Meta',
            'language' => 'en_US',
            'variables' => [],
            'is_active' => true,
        ]);

        // Plantilla Email de prueba
        Template::create([
            'name' => 'Bienvenida Email',
            'channel' => 'email',
            'subject' => 'Bienvenido a {{nombre}}',
            'body' => 'Hola {{nombre}}, este es un correo de prueba del servicio multicanal.',
            'language' => 'es_MX',
            'variables' => ['nombre'],
            'is_active' => true,
        ]);

        $this->command->info('Seed completo: usuario, canales, providers y plantillas creados.');
    }
}
