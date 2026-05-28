<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            // Almacenados en claro (el usuario es responsable de sus contactos)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Metadatos personalizados por usuario: {"empresa": "Acme", "plan": "pro"}
            $table->json('metadata')->nullable();

            // Etiquetas para segmentación: ["vip", "newsletter"]
            $table->json('tags')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
