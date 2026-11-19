<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confessions', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 10);
            $table->string('category_key', 40)->nullable();
            $table->string('status', 24)->default('draft');
            $table->string('title', 180);
            $table->text('body');
            $table->text('excerpt')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->index(['locale', 'status', 'published_at', 'id'], 'confessions_public_feed');
            $table->index(['locale', 'category_key', 'status', 'published_at', 'id'], 'confessions_public_category_feed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confessions');
    }
};
