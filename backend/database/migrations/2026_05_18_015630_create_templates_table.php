<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // nombre interno
            $table->string('whatsapp_template_name')->nullable(); // nombre aprobado en Meta (ej. hello_world)
            $table->enum('channel', ['email', 'whatsapp']);
            $table->string('subject')->nullable();           // para email
            $table->text('body');                            // contenido con {{variables}}
            $table->string('language', 10)->default('es_MX');
            $table->json('variables')->nullable();           // lista de variables esperadas
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};