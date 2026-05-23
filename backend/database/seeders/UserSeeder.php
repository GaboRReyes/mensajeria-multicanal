<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            [
                'email' => 'test@example.com',
            ],
            [
                'name' => 'Administrador',
                'password' => bcrypt('password'),
            ]
        );

        User::factory()->count(5)->create();
    }
}