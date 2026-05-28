<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role: admin | client | developer
            $table->string('role')->default('client')->after('email');
            // Control de cuenta
            $table->boolean('is_active')->default(true)->after('role');
            // Cuotas mensuales (null = sin límite)
            $table->unsignedInteger('monthly_limit')->nullable()->after('is_active');
            $table->unsignedInteger('used_this_month')->default(0)->after('monthly_limit');
            $table->timestamp('quota_reset_at')->nullable()->after('used_this_month');

            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'monthly_limit', 'used_this_month', 'quota_reset_at']);
        });
    }
};
