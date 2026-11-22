<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_submissions', function (Blueprint $table): void {
            $table->dropUnique('reporter_once_per_group');
            $table->unique(['report_group_id', 'reporter_hash', 'payload_fingerprint'], 'reporter_fact_once_per_group');
        });
    }

    public function down(): void
    {
        if (DB::table('report_submissions')->select('report_group_id', 'reporter_hash')
            ->groupBy('report_group_id', 'reporter_hash')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore the old report constraint while distinct facts from one reporter exist.');
        }

        Schema::table('report_submissions', function (Blueprint $table): void {
            $table->dropUnique('reporter_fact_once_per_group');
            $table->unique(['report_group_id', 'reporter_hash'], 'reporter_once_per_group');
        });
    }
};
