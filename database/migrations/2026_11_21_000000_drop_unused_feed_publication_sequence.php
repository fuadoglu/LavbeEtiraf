<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropIndex('confessions_publication_sequence_index');
            $table->dropColumn('publication_sequence');
        });
        Schema::dropIfExists('feed_publications');
    }

    public function down(): void
    {
        Schema::create('feed_publications', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at');
        });
        Schema::table('confessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('publication_sequence')->nullable()->index();
        });
    }
};
