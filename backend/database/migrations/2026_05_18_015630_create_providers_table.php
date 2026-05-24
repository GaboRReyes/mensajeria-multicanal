<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('name');                        // ej. "Brevo SMTP", "Meta Cloud API"
            $table->string('driver');                      // ej. "smtp", "meta_cloud"
            $table->json('config')->nullable();            // credenciales/ajustes (encriptado a nivel app)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['channel_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};