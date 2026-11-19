<?php

namespace App\Http\Controllers;

use App\Models\Confession;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicConfessionController extends Controller
{
    public function show(Request $request, string $locale, string $publicId, LocaleRegistry $locales): Response|View
    {
        $localeRecord = $locales->bySegment($locale);
        abort_unless($localeRecord, 404);

        $confession = Confession::query()->with('publicRevision')
            ->where('public_id', $publicId)
            ->where('locale', $locale)
            ->first();

        if ($confession?->status === 'deleted') {
            abort_if($confession->ever_public, 410);
            abort(404);
        }

        abort_unless(
            $confession
            && $confession->status === 'public'
            && $confession->published_at
            && $confession->public_revision_id
            && $confession->publicRevision?->status === 'approved',
            404,
        );

        $path = '/'.$locale.'/confession/'.$confession->public_id;
        $canonical = rtrim((string) config('app.url'), '/').$path;
        $revision = $confession->publicRevision;
        $description = $confession->excerpt ?: mb_substr(trim(preg_replace('/\s+/u', ' ', $revision->body) ?? ''), 0, 240, 'UTF-8');
        $category = $locales->category((string) $revision->category_key);

        return response()->view('confessions.public', compact('confession', 'revision', 'canonical', 'description', 'category', 'localeRecord'))
            ->header('Cache-Control', 'public, no-cache, must-revalidate')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
