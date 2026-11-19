<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->string('public_id', 43)->nullable()->unique();
            $table->string('author_alias', 15)->nullable();
            $table->string('response_expectation', 16)->nullable();
            $table->timestamp('age_confirmed_at')->nullable();
            $table->char('submission_key_hash', 64)->nullable()->unique();
            $table->char('payload_fingerprint', 64)->nullable();
        });

        Schema::create('confession_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('category_key', 40)->nullable();
            $table->string('response_expectation', 16)->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->unique(['confession_id', 'revision']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('confession_ownerships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->unique()->constrained()->cascadeOnDelete();
            $table->char('owner_token_hash', 64);
            $table->char('recovery_token_hash', 64);
            $table->unsignedInteger('credential_version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confession_ownerships');
        Schema::dropIfExists('confession_revisions');
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropUnique(['submission_key_hash']);
            $table->dropColumn(['public_id', 'author_alias', 'response_expectation', 'age_confirmed_at', 'submission_key_hash', 'payload_fingerprint']);
        });
    }
};
