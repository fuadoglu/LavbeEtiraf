<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Category taxonomy correction: loss stories move to the new combined loss/new beginnings category.
            // Revision snapshots are updated as part of this explicit taxonomy migration so public pages,
            // moderation, and recommendation queries all resolve the same category.
            DB::table('confession_revisions')
                ->where('category_key', 'grief')
                ->update(['category_key' => 'life-changes']);

            DB::table('confessions')
                ->where('category_key', 'grief')
                ->update(['category_key' => 'life-changes']);
        });
    }

    public function down(): void
    {
        // Intentionally irreversible: rows originally in `life-changes` cannot be distinguished from moved rows.
    }
};
