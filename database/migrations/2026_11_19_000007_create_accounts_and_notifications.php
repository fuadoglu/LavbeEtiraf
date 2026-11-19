<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('preferred_locale', 10)->default('en');
            $table->boolean('in_app_notifications')->default(true);
            $table->boolean('email_notifications')->default(false);
        });

        Schema::table('confession_ownerships', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('confession_id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'confession_id'], 'confession_owner_account');
        });

        Schema::table('comment_ownerships', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('comment_id')->constrained()->nullOnDelete();
            $table->index(['user_id', 'comment_id'], 'comment_owner_account');
        });

        Schema::create('confession_follows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'confession_id'], 'confession_follow_once');
            $table->index(['confession_id', 'user_id'], 'confession_follow_recipients');
        });

        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('event_key', 64);
            $table->string('event_type', 32);
            $table->boolean('in_app')->default(true);
            $table->timestamp('read_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('email_skipped_at')->nullable();
            $table->timestamp('email_claimed_at')->nullable();
            $table->unsignedSmallInteger('email_attempts')->default(0);
            $table->string('last_error_code', 40)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'event_key'], 'user_notification_event_once');
            $table->index(['user_id', 'in_app', 'created_at'], 'user_notification_inbox');
            $table->index(['email_sent_at', 'email_skipped_at', 'email_claimed_at', 'created_at'], 'user_notification_email_pending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('confession_follows');
        Schema::table('comment_ownerships', function (Blueprint $table): void {
            $table->dropIndex('comment_owner_account');
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('confession_ownerships', function (Blueprint $table): void {
            $table->dropIndex('confession_owner_account');
            $table->dropConstrainedForeignId('user_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['preferred_locale', 'in_app_notifications', 'email_notifications']);
        });
    }
};
