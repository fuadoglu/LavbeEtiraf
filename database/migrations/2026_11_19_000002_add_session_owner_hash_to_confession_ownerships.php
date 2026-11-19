<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_ownerships', function (Blueprint $table): void {
            $table->char('session_token_hash', 64)->nullable()->after('confession_id');
        });
    }

    public function down(): void
    {
        Schema::table('confession_ownerships', function (Blueprint $table): void {
            $table->dropColumn('session_token_hash');
        });
    }
};
