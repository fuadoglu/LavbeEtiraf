<?php

use App\Support\Lavbe\AuthorAlias;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $allocate = static fn (): string => AuthorAlias::generateAvailable();

        $duplicates = DB::table('confessions')->whereNotNull('author_alias')
            ->select('author_alias')->groupBy('author_alias')->havingRaw('COUNT(*) > 1')
            ->pluck('author_alias');
        foreach ($duplicates as $alias) {
            $ids = DB::table('confessions')->where('author_alias', $alias)->orderBy('id')->pluck('id');
            foreach ($ids->skip(1) as $id) {
                DB::table('confessions')->where('id', $id)->update(['author_alias' => $allocate()]);
            }
        }
        DB::table('confessions')->whereNull('author_alias')->orderBy('id')->select('id')
            ->chunkById(100, function ($rows) use ($allocate): void {
                foreach ($rows as $row) {
                    DB::table('confessions')->where('id', $row->id)->update(['author_alias' => $allocate()]);
                }
            });

        Schema::table('confessions', function (Blueprint $table): void {
            $table->unique('author_alias', 'confessions_author_alias_unique');
        });
    }

    public function down(): void
    {
        Schema::table('confessions', function (Blueprint $table): void {
            $table->dropUnique('confessions_author_alias_unique');
        });
    }
};
