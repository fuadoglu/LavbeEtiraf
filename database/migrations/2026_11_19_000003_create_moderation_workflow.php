<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('visibility_version')->default(1);
            $table->boolean('ever_public')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('republish_requested_at')->nullable();
        });

        Schema::table('confession_revisions', function (Blueprint $table): void {
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('moderator_message')->nullable();
        });

        Schema::table('confessions', function (Blueprint $table): void {
            $table->foreignId('public_revision_id')->nullable()->constrained('confession_revisions')->nullOnDelete();
        });

        Schema::create('moderator_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->string('locale', 10)->default('*');
            $table->boolean('is_active')->default(true);
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role', 'locale'], 'moderator_grants_actor_role_locale_unique');
            $table->index(['locale', 'role', 'is_active', 'user_id'], 'moderator_grants_lookup');
        });

        Schema::create('moderator_mfas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('secret_ciphertext')->nullable();
            $table->text('pending_secret_ciphertext')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('last_counter')->nullable();
            $table->timestamps();
        });

        Schema::create('moderator_recovery_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('code_hash', 64)->unique();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'used_at']);
        });

        Schema::create('moderation_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('grant_snapshot')->nullable();
            $table->string('target_type', 32);
            $table->string('target_key', 64);
            $table->string('target_public_id', 43)->nullable();
            $table->unsignedBigInteger('revision_id')->nullable();
            $table->string('decision', 32);
            $table->string('reason_code', 40)->nullable();
            $table->string('reason', 500)->nullable();
            $table->char('internal_note_hash', 64)->nullable();
            $table->char('author_message_hash', 64)->nullable();
            $table->string('previous_state', 32)->nullable();
            $table->string('new_state', 32)->nullable();
            $table->unsignedInteger('expected_lock_version')->nullable();
            $table->unsignedInteger('resulting_lock_version')->nullable();
            $table->char('previous_public_revision_hash', 64)->nullable();
            $table->char('new_public_revision_hash', 64)->nullable();
            $table->char('idempotency_hash', 64)->nullable()->unique();
            $table->char('idempotency_fingerprint', 64)->nullable();
            $table->uuid('request_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['target_type', 'target_key', 'created_at'], 'moderation_audits_target_time');
            $table->index(['actor_user_id', 'created_at'], 'moderation_audits_actor_time');
        });

        Schema::create('moderation_outbox', function (Blueprint $table): void {
            $table->id();
            $table->char('event_key', 64)->unique();
            $table->string('target_public_id', 43);
            $table->string('event_type', 32);
            $table->unsignedBigInteger('visibility_version');
            $table->unsignedBigInteger('revision_id')->nullable();
            $table->char('revision_hash', 64)->nullable();
            $table->string('locale', 10);
            $table->string('category_key', 40)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error_code', 40)->nullable();
            $table->timestamps();
            $table->index(['dispatched_at', 'occurred_at'], 'moderation_outbox_pending');
            $table->index(['target_public_id', 'visibility_version'], 'moderation_outbox_target_generation');
        });

        DB::table('confessions')->where('status', 'public')->whereNotNull('published_at')->update(['ever_public' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_outbox');
        Schema::dropIfExists('moderation_audits');
        Schema::dropIfExists('moderator_recovery_codes');
        Schema::dropIfExists('moderator_mfas');
        Schema::dropIfExists('moderator_grants');
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropForeign(['public_revision_id']);
            $table->dropColumn(['public_revision_id', 'lock_version', 'visibility_version', 'ever_public', 'deleted_at', 'republish_requested_at']);
        });
        Schema::table('confession_revisions', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropColumn(['reviewed_by_user_id', 'reviewed_at', 'moderator_message']);
        });
    }
};
