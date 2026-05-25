<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'whatsapp_template_name' => null,
            'channel' => 'whatsapp',
            'subject' => null,
            'body' => 'Hola {{nombre}}',
            'language' => 'es_MX',
            'variables' => ['nombre'],
            'is_active' => true,
        ];
    }

    // Variante para WhatsApp con plantilla aprobada
    public function whatsapp(): static
    {
        return $this->state(fn () => [
            'channel' => 'whatsapp',
            'whatsapp_template_name' => 'hello_world',
            'language' => 'en_US',
            'variables' => [],
        ]);
    }

    // Variante para Email
    public function email(): static
    {
        return $this->state(fn () => [
            'channel' => 'email',
            'subject' => 'Asunto de prueba',
            'whatsapp_template_name' => null,
            'language' => 'es_MX',
        ]);
    }
}