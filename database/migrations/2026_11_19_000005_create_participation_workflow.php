<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->boolean('reviews_open')->default(true);
            $table->unsignedInteger('support_count')->default(0);
        });

        Schema::create('confession_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('status', 20)->default('unpublished');
            $table->string('author_alias', 15);
            $table->text('body');
            $table->unsignedBigInteger('public_revision_id')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('visibility_version')->default(1);
            $table->char('idempotency_hash', 64)->nullable()->unique();
            $table->char('idempotency_fingerprint', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['confession_id', 'status', 'published_at', 'id'], 'comments_public_parent');
        });

        Schema::create('confession_comment_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('confession_comments')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['comment_id', 'revision']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('confession_comments', function (Blueprint $table): void {
            $table->foreign('public_revision_id')->references('id')->on('confession_comment_revisions')->nullOnDelete();
        });

        Schema::create('comment_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->unique()->constrained('confession_comments')->cascadeOnDelete();
            $table->char('session_token_hash', 64)->nullable()->index();
            $table->char('owner_token_hash', 64)->nullable()->index();
            $table->char('recovery_token_hash', 64);
            $table->unsignedInteger('credential_version')->default(1);
            $table->timestamps();
        });

        Schema::create('comment_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->unique()->constrained('confession_comments')->cascadeOnDelete();
            $table->string('status', 20)->default('unpublished');
            $table->text('body');
            $table->unsignedBigInteger('public_revision_id')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('visibility_version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comment_response_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('response_id')->constrained('comment_responses')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['response_id', 'revision']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('comment_responses', function (Blueprint $table): void {
            $table->foreign('public_revision_id')->references('id')->on('comment_response_revisions')->nullOnDelete();
        });

        Schema::create('confession_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->char('participant_hash', 64);
            $table->string('author_alias', 15);
            $table->timestamps();
            $table->unique(['confession_id', 'participant_hash'], 'confession_participant_identity_unique');
        });

        Schema::create('confession_continuations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->uuid('public_id')->unique();
            $table->string('status', 20)->default('unpublished');
            $table->text('body');
            $table->unsignedBigInteger('public_revision_id')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->unsignedBigInteger('visibility_version')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['confession_id', 'status', 'published_at', 'id'], 'continuations_public_parent');
        });

        Schema::create('confession_continuation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('continuation_id')->constrained('confession_continuations')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['continuation_id', 'revision'], 'continuation_revision_unique');
            $table->index(['status', 'created_at']);
        });

        Schema::table('confession_continuations', function (Blueprint $table): void {
            $table->foreign('public_revision_id')->references('id')->on('confession_continuation_revisions')->nullOnDelete();
        });

        Schema::create('confession_support_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->char('participant_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->char('last_idempotency_hash', 64)->nullable();
            $table->char('last_payload_fingerprint', 64)->nullable();
            $table->timestamps();
            $table->unique(['confession_id', 'participant_hash'], 'confession_support_participant_unique');
        });

        Schema::create('participation_idempotencies', function (Blueprint $table): void {
            $table->id();
            $table->char('idempotency_hash', 64)->unique();
            $table->char('payload_fingerprint', 64);
            $table->string('target_type', 24);
            $table->unsignedBigInteger('target_id');
            $table->string('action', 24);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('participation_outbox', function (Blueprint $table): void {
            $table->id();
            $table->char('event_key', 64)->unique();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->unsignedBigInteger('visibility_version');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error_code', 40)->nullable();
            $table->timestamps();
            $table->index(['dispatched_at', 'occurred_at'], 'participation_outbox_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participation_outbox');
        Schema::dropIfExists('participation_idempotencies');
        Schema::dropIfExists('confession_support_reactions');
        Schema::table('comment_responses', function (Blueprint $table): void {
            $table->dropForeign(['public_revision_id']);
        });
        Schema::dropIfExists('comment_response_revisions');
        Schema::dropIfExists('comment_responses');
        Schema::dropIfExists('confession_participants');
        Schema::table('confession_continuations', function (Blueprint $table): void {
            $table->dropForeign(['public_revision_id']);
        });
        Schema::dropIfExists('confession_continuation_revisions');
        Schema::dropIfExists('confession_continuations');
        Schema::dropIfExists('comment_ownerships');
        Schema::table('confession_comments', function (Blueprint $table): void {
            $table->dropForeign(['public_revision_id']);
        });
        Schema::dropIfExists('confession_comment_revisions');
        Schema::dropIfExists('confession_comments');
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropColumn(['reviews_open', 'support_count']);
        });
    }
};
