<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Canales activos para esta campaña: ['email','whatsapp']
            $table->json('channels');

            // Variables globales de la campaña (se fusionan con las del contacto)
            $table->json('variables')->nullable();

            // Estados del ciclo de vida
            $table->enum('status', [
                'draft',        // borrador, no enviada
                'scheduled',    // programada para una fecha
                'processing',   // generando mensajes
                'sending',      // mensajes en cola
                'completed',    // todos enviados
                'failed',       // error crítico
                'cancelled',    // cancelada por el usuario
            ])->default('draft');

            // Estadísticas desnormalizadas (actualizadas por jobs)
            $table->unsignedInteger('total_contacts')->default(0);
            $table->unsignedInteger('total_messages')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
