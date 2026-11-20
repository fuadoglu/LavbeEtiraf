<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->text('p256dh');
            $table->text('auth');
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'active', 'created_at'], 'push_subscription_owner_active');
        });

        Schema::create('web_push_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_notification_id')->constrained('user_notifications')->cascadeOnDelete();
            $table->foreignId('push_subscription_id')->constrained('push_subscriptions')->cascadeOnDelete();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('claimed_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->string('last_error_code', 40)->nullable();
            $table->timestamps();
            $table->unique(['user_notification_id', 'push_subscription_id'], 'web_push_delivery_once');
            $table->index(['sent_at', 'failed_at', 'next_attempt_at', 'claimed_until'], 'web_push_delivery_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_push_deliveries');
        Schema::dropIfExists('push_subscriptions');
    }
};
