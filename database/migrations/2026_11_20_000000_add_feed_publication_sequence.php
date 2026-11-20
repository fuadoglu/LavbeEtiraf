<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_publications', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at');
        });
        Schema::table('confessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('publication_sequence')->nullable()->index();
        });

        DB::table('confessions')->where('status', 'public')->orderBy('id')->chunkById(500, function ($confessions): void {
            foreach ($confessions as $confession) {
                $sequence = DB::table('feed_publications')->insertGetId(['created_at' => now()]);
                DB::table('confessions')->where('id', $confession->id)->update(['publication_sequence' => $sequence]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropColumn('publication_sequence');
        });
        Schema::dropIfExists('feed_publications');
    }
};
