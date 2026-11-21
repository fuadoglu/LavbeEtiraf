<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participation_outbox', function (Blueprint $table): void {
            $table->unsignedBigInteger('follower_cursor_id')->nullable()->after('target_id');
        });
    }

    public function down(): void
    {
        Schema::table('participation_outbox', function (Blueprint $table): void {
            $table->dropColumn('follower_cursor_id');
        });
    }
};
