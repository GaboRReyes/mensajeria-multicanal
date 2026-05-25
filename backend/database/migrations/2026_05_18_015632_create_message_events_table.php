<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');                      // FK al mensaje (messages.id es uuid)
            $table->string('status');                        // estado registrado en este evento
            $table->json('payload')->nullable();             // datos crudos del webhook/intento
            $table->text('error')->nullable();               // detalle si fue un fallo
            $table->timestamp('occurred_at')->useCurrent();  // cuándo ocurrió
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->cascadeOnDelete();
            $table->index(['message_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_events');
    }
};