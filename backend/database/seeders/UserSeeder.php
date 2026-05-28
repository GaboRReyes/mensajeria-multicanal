<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('password'),
                'role'     => 'admin',
                'is_active'=> true,
            ]
        );

        // ── Cliente demo ───────────────────────────────────────────────────
        $client = User::firstOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name'          => 'Cliente Demo',
                'password'      => bcrypt('password'),
                'role'          => 'client',
                'is_active'     => true,
                'monthly_limit' => 1000,
                'quota_reset_at'=> now()->addMonth()->startOfMonth(),
            ]
        );

        // ── Developer demo ─────────────────────────────────────────────────
        $dev = User::firstOrCreate(
            ['email' => 'developer@example.com'],
            [
                'name'          => 'Developer Demo',
                'password'      => bcrypt('password'),
                'role'          => 'developer',
                'is_active'     => true,
                'monthly_limit' => 500,
                'quota_reset_at'=> now()->addMonth()->startOfMonth(),
            ]
        );

        // Generar API Key de ejemplo para el developer
        if ($dev->apiKeys()->count() === 0) {
            ['plain' => $plain] = ApiKey::generate(
                userId   : $dev->id,
                name     : 'Key de desarrollo',
                abilities: ['messages:write', 'messages:read'],
                env      : 'test',
            );
            $this->command->info("API Key del developer: {$plain}");
        }

        // Usuario legado (mantiene compatibilidad con test@example.com)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => bcrypt('password'),
                'role'     => 'client',
                'is_active'=> true,
            ]
        );
    }
}
