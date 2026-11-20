<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_comments', function (Blueprint $table): void {
            $table->foreignId('parent_comment_id')->nullable()->after('confession_id')
                ->constrained('confession_comments')->nullOnDelete();
            $table->unsignedInteger('reaction_count')->default(0)->after('visibility_version');
            $table->index(['parent_comment_id', 'status', 'published_at'], 'comment_thread_public');
        });

        Schema::create('confession_comment_reactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained('confession_comments')->cascadeOnDelete();
            $table->char('participant_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['comment_id', 'participant_hash'], 'comment_reaction_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confession_comment_reactions');
        Schema::table('confession_comments', function (Blueprint $table): void {
            $table->dropIndex('comment_thread_public');
            $table->dropConstrainedForeignId('parent_comment_id');
            $table->dropColumn('reaction_count');
        });
    }
};
