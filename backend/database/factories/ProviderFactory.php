<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'name' => $this->faker->company(),
            'driver' => 'meta_cloud',
            'config' => [],
            'is_active' => true,
        ];
    }
}