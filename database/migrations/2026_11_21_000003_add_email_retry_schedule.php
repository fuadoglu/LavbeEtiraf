<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->timestamp('email_next_attempt_at')->nullable();
            $table->timestamp('email_failed_at')->nullable();
            $table->index(['email_failed_at', 'email_next_attempt_at'], 'user_notification_email_retry');
        });
    }

    public function down(): void
    {
        Schema::table('user_notifications', function (Blueprint $table): void {
            $table->dropIndex('user_notification_email_retry');
            $table->dropColumn(['email_next_attempt_at', 'email_failed_at']);
        });
    }
};
