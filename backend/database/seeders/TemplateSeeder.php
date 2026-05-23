<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Template::query()->insert([
            [
                'name' => 'Invitacion',
                'subject' => 'Invitación a nuestro evento',
                'content' => "Hola {name},\n\nTe invitamos cordialmente a nuestro evento el próximo {date}.\n\nSaludos,\nEquipo",
                'channel' => 'whatsapp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Saludo de empresa',
                'subject' => 'Bienvenida a la empresa',
                'content' => "Estimado/a {name},\n\nLe damos la bienvenida a {company}. Estamos encantados de contar con usted.\n\nAtentamente,\nEquipo",
                'channel' => 'email',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
