<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('template_id')->nullable()->constrained();
    $table->foreignId('provider_id')->constrained();
    $table->enum('channel', ['email', 'whatsapp']);
    $table->string('recipient_hash', 64);   // SHA-256 del destinatario
    $table->string('recipient_masked');     // ej. j***@itc.mx
    $table->json('variables')->nullable();
    $table->string('idempotency_key')->nullable()->unique();
    $table->enum('status', [
        'programado','encolado','enviado','entregado','leido','fallido','cancelado'
    ])->default('encolado');
    $table->string('provider_message_id')->nullable();
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->json('last_error')->nullable();
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
 
    $table->index(['user_id','status']);
    $table->index(['channel','status']);
    $table->index('scheduled_at');
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
