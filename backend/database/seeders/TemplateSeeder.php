<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Invitacion',
                'whatsapp_template_name' => 'Invitación a nuestro evento',
                'body' => "Hola {name},\n\nTe invitamos cordialmente a nuestro evento el próximo {date}.\n\nSaludos,\nEquipo",
                'channel' => 'email',
                'language' => 'en_US',
                'variables' => [],
                'is_active' => true,
            ],
            [
                'name' => 'Saludo de empresa',
                'whatsapp_template_name' => 'Bienvenida a la empresa',
                'body' => "Estimado/a {name},\n\nLe damos la bienvenida a {company}. Estamos encantados de contar con usted.\n\nAtentamente,\nEquipo",
                'channel' => 'email',
                'language' => 'en_US',
                'variables' => [],
                'is_active' => true,
            ],
            [
                'name' => 'Hola Mundo (WhatsApp)',
                'whatsapp_template_name' => 'hello_world',
                'channel' => 'whatsapp',
                'body' => 'Plantilla de bienvenida de prueba de Meta',
                'language' => 'en_US',
                'variables' => [],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}