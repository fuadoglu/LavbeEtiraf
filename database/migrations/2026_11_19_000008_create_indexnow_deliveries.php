<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indexnow_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('moderation_outbox_id')->constrained('moderation_outbox')->cascadeOnDelete();
            $table->char('event_key', 64)->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('claimed_until')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->string('last_error_code', 40)->nullable();
            $table->timestamps();
            $table->index(['delivered_at', 'failed_at', 'next_attempt_at'], 'indexnow_delivery_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indexnow_deliveries');
    }
};
