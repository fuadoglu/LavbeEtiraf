<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_readers', function (Blueprint $table): void {
            $table->timestamp('last_seen_at')->nullable()->index();
        });

        DB::table('confession_readers')->whereNull('last_seen_at')->update(['last_seen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('confession_readers', function (Blueprint $table): void {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });
    }
};
