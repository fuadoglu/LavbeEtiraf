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
            $table->timestamp('public_updated_at')->nullable()->after('published_at');
            $table->index(['locale', 'status', 'public_updated_at', 'id'], 'confessions_public_sitemap');
        });

        DB::table('confessions')
            ->where('status', 'public')
            ->whereNotNull('published_at')
            ->update(['public_updated_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropIndex('confessions_public_sitemap');
            $table->dropColumn('public_updated_at');
        });
    }
};
