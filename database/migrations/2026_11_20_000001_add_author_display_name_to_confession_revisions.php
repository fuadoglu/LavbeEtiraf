<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('confession_revisions', function (Blueprint $table): void {
            $table->string('author_display_name', 120)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('confession_revisions', function (Blueprint $table): void {
            $table->dropColumn('author_display_name');
        });
    }
};
