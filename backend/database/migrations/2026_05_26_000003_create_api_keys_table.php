<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Nombre descriptivo: "Producción", "App móvil", etc.
            $table->string('name');

            // Prefijo visible: sk_live_xxxx (primeros 12 chars)
            $table->string('prefix', 16)->index();

            // Hash SHA-256 de la clave completa (nunca en claro)
            $table->string('hashed_key', 64)->unique();

            // Permisos granulares: ['messages:write','messages:read','campaigns:read']
            $table->json('abilities')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
