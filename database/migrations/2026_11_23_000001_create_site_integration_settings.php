<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_integration_settings', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('ga4_enabled')->default(false);
            $table->string('ga4_measurement_id', 32)->nullable();
            $table->boolean('ads_enabled')->default(false);
            $table->string('adsense_client_id', 32)->nullable();
            $table->boolean('story_slot_enabled')->default(false);
            $table->string('story_slot_id', 32)->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();
        });

        DB::table('site_integration_settings')->insert([
            'id' => 1,
            'ga4_enabled' => false,
            'ads_enabled' => false,
            'story_slot_enabled' => false,
            'lock_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('confessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('ad_review_visibility_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('confessions', fn (Blueprint $table) => $table->dropColumn('ad_review_visibility_version'));
        Schema::dropIfExists('site_integration_settings');
    }
};
