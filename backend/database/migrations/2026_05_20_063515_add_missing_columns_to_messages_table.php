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
    Schema::table('messages', function (Blueprint $table) {
        $table->text('topic')->nullable()->after('template_id');        // ← nullable primero
        $table->text('extension')->nullable()->after('topic');          // ← nullable primero
        $table->text('event')->nullable()->after('channel');
        $table->boolean('private')->nullable()->after('recipient_masked');
        $table->jsonb('payload')->nullable()->after('variables');
        $table->timestamp('inserted_at')->nullable()->after('idempotency_key');
    });
}

public function down(): void
{
    Schema::table('messages', function (Blueprint $table) {
        $table->dropColumn(['topic', 'extension', 'event', 'private', 'payload', 'inserted_at']);
    });
}
};
