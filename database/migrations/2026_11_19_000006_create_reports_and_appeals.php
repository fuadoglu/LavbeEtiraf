<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type', 16);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 12);
            $table->string('status', 16)->default('pending');
            $table->string('priority', 16)->default('normal');
            $table->unsignedInteger('report_count')->default(0);
            $table->string('last_reason_code', 32);
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolution', 16)->nullable();
            $table->timestamp('first_reported_at');
            $table->timestamp('last_reported_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['target_type', 'target_id', 'channel'], 'report_target_channel_unique');
            $table->index(['status', 'priority', 'created_at'], 'report_queue_priority');
            $table->index(['confession_id', 'status'], 'report_parent_status');
        });

        Schema::create('report_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_group_id')->constrained()->cascadeOnDelete();
            $table->char('reporter_hash', 64);
            $table->char('idempotency_hash', 64)->unique();
            $table->char('payload_fingerprint', 64);
            $table->string('reason_code', 32);
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['report_group_id', 'reporter_hash'], 'reporter_once_per_group');
            $table->index(['report_group_id', 'created_at'], 'report_submission_group_time');
        });

        Schema::create('moderation_appeals', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type', 16);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hide_audit_id')->unique()->constrained('moderation_audits')->restrictOnDelete();
            $table->char('owner_hash', 64);
            $table->char('idempotency_hash', 64)->unique();
            $table->char('payload_fingerprint', 64);
            $table->text('details');
            $table->string('status', 16)->default('pending');
            $table->string('resolution', 16)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolution_audit_id')->nullable()->constrained('moderation_audits')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'appeal_queue_status_time');
            $table->index(['confession_id', 'status'], 'appeal_parent_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_appeals');
        Schema::dropIfExists('report_submissions');
        Schema::dropIfExists('report_groups');
    }
};
