<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // null = mensaje individual; uuid = parte de una campaña
            $table->uuid('campaign_id')->nullable()->after('user_id');
            $table->uuid('contact_id')->nullable()->after('campaign_id');

            $table->foreign('campaign_id')->references('id')->on('campaigns')->nullOnDelete();
            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['contact_id']);
            $table->dropColumn(['campaign_id', 'contact_id']);
        });
    }
};
