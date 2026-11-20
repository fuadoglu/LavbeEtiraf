<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('unique_read_count')->default(0);
        });

        Schema::create('confession_readers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('confession_id')->constrained()->cascadeOnDelete();
            $table->char('reader_hash', 64);
            $table->unique(['confession_id', 'reader_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confession_readers');

        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropColumn('unique_read_count');
        });
    }
};
