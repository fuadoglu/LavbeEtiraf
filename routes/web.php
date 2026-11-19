<?php

use App\Http\Controllers\GuestConfessionController;
use App\Http\Controllers\ModerationAdminController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ModeratorAuthController;
use App\Http\Controllers\PublicConfessionController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\PrivateResponseHeaders;
use App\Http\Middleware\RequireFreshModeratorMfa;
use App\Http\Middleware\RequireModeratorAccount;
use App\Http\Middleware\RequireModeratorAdmin;
use App\Http\Middleware\SetLocale;
use App\Support\Lavbe\FeedCursor;
use App\Support\Lavbe\FeedQuery;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');

Route::get('/', function (Request $request, LocaleRegistry $locales) {
    $locale = $locales->resolveTag((string) $request->cookie('lavbe_locale'))
        ?? $locales->fromAcceptLanguage([(string) $request->header('Accept-Language', '')])
        ?? $locales->fromCountry($request->header('CF-IPCountry'), (string) $request->header('Accept-Language', ''))
        ?? $locales->fallback();

    return redirect()->route('home', ['locale' => $locale['segment']], 302);
})->name('root');

Route::prefix('{locale}')->where(['locale' => '[a-z]{2}(?:-[a-z]{2})?'])->middleware(SetLocale::class)->group(function (): void {
    Route::get('/', function (string $locale, Request $request, LocaleRegistry $locales, FeedQuery $feed) {
        abort_unless($locales->bySegment($locale), 404);
        $sort = $request->query('sort', 'new');
        abort_unless(in_array($sort, array_keys(config('lavbe.feed_orders')), true), 404);
        $category = $request->query('category');
        if ($category !== null) {
            return redirect()->route('topic.show', ['locale' => $request->route('locale'), 'category' => $category, 'sort' => $sort]);
        }

        $categories = collect($locales->catalog()['categories'])
            ->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en']])
            ->all();

        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate(12)->withQueryString();

        $canonical = route('home', ['locale' => $locale], false).($confessions->currentPage() > 1 ? '?page='.$confessions->currentPage() : '');

        return view('home', [
            'category' => $category,
            'sort' => $sort,
            'locale' => $request->attributes->get('lavbe.locale'),
            'categories' => $categories,
            'confessions' => $confessions,
            'feedCursor' => $confessions->currentPage() === 1 ? app(FeedCursor::class)->issue(app()->getLocale(), $category, $sort, $confessions->lastItem() ?? 0) : null,
            'canonical' => $canonical,
            'categoryIndexable' => $category === null || $confessions->total() >= (int) config('seo.category_min_public_items', 5),
        ]);
    })->name('home');

    Route::get('/sitemap.xml', [SitemapController::class, 'locale'])->defaults('page', 1)->name('sitemap.locale');
    Route::get('/sitemap-{page}.xml', [SitemapController::class, 'locale'])->whereNumber('page')->name('sitemap.locale.page');

    Route::get('/confession/{publicId}', [PublicConfessionController::class, 'show'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->name('confessions.public');

    Route::post('/language', function (string $localeSegment, Request $request, LocaleRegistry $locales) {
        abort_unless($locales->bySegment($localeSegment), 404);
        $validated = $request->validate(['locale' => ['required', 'string', 'max:10']]);
        $locale = $locales->bySegment($validated['locale']);
        abort_unless($locale, 422);

        $returnTo = (string) $request->input('return_to', '/'.$locale['segment'].'/');
        $path = parse_url((string) $returnTo, PHP_URL_PATH);
        $query = parse_url((string) $returnTo, PHP_URL_QUERY);
        if (! is_string($path) || ! preg_match('#^/[a-z]{2}(?:-[a-z]{2})?(?:/topics/[a-z0-9-]+)?/?$#', $path)) {
            $path = '/'.$locale['segment'].'/';
            $query = null;
        } else {
            $path = preg_replace('#^/[a-z]{2}(?:-[a-z]{2})?(?=/|$)#', '/'.$locale['segment'], $path);
            parse_str((string) $query, $queryParameters);
            unset($queryParameters['cursor']);
            $query = http_build_query($queryParameters);
        }
        $destination = $path.($query ? '?'.$query : '');
        $response = response('', 303)->header('Location', $destination);

        return $response->withCookie(cookie('lavbe_locale', $locale['segment'], 60 * 24 * 365, '/', null, $request->isSecure(), true, false, 'lax'));
    })->name('locale.select');

    Route::get('/topics/{category}', function (string $locale, string $category, Request $request, LocaleRegistry $locales, FeedQuery $feed) {
        abort_unless($locales->bySegment($locale), 404);
        abort_unless($locales->category($category), 404);
        $sort = $request->query('sort', 'new');
        abort_unless(in_array($sort, array_keys(config('lavbe.feed_orders')), true), 404);

        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate(12)->withQueryString();

        $canonical = route('topic.show', ['locale' => $locale, 'category' => $category], false).($confessions->currentPage() > 1 ? '?page='.$confessions->currentPage() : '');

        return view('home', [
            'category' => $category,
            'sort' => $sort,
            'locale' => $request->attributes->get('lavbe.locale'),
            'categories' => collect($locales->catalog()['categories'])
                ->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en']])
                ->all(),
            'confessions' => $confessions,
            'feedCursor' => $confessions->currentPage() === 1 ? app(FeedCursor::class)->issue(app()->getLocale(), $category, $sort, $confessions->lastItem() ?? 0) : null,
            'canonical' => $canonical,
            'categoryIndexable' => $confessions->total() >= (int) config('seo.category_min_public_items', 5),
        ]);
    })->name('topic.show');

    Route::get('/feed-fragment', function (string $locale, Request $request, LocaleRegistry $locales, FeedQuery $feed, FeedCursor $cursors) {
        abort_unless($locales->bySegment($locale), 404);
        $order = (string) $request->query('order', 'new');
        abort_unless(in_array($order, array_keys(config('lavbe.feed_orders')), true), 400);
        $category = $request->query('category');
        $category = $category === null ? null : (string) $category;
        abort_if($category !== null && ! $locales->category($category), 400);
        $position = $cursors->read((string) $request->query('cursor', ''), app()->getLocale(), $category, $order)['offset'];
        $items = $feed->build(app()->getLocale(), $category, $order)->skip($position)->take(13)->get();
        $hasMore = $items->count() > 12;
        $items = $items->take(12);
        $nextPosition = $position + $items->count();
        $nextCursor = $hasMore ? $cursors->issue(app()->getLocale(), $category, $order, $nextPosition) : null;

        return response()->json([
            'html' => view('components.feed-items', ['confessions' => $items])->render(),
            'next_url' => $nextCursor ? route('feed.fragment', ['locale' => $locale, 'order' => $order, 'category' => $category, 'cursor' => $nextCursor]) : null,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
        ])->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'no-store');
    })->name('feed.fragment');

    Route::match(['GET', 'POST'], '/search', function (string $locale, Request $request, LocaleRegistry $locales, FeedQuery $feed) {
        abort_unless($locales->bySegment($locale), 404);
        $query = trim((string) ($request->isMethod('post') ? $request->input('q', '') : $request->query('q', '')));
        $results = null;
        if ($query !== '') {
            $results = $feed->build(app()->getLocale(), null, 'new')
                ->where(function ($builder) use ($query): void {
                    $builder->where('title', 'like', '%'.$query.'%')->orWhere('body', 'like', '%'.$query.'%');
                })->paginate(12);
        }

        return response()->view('search', compact('query', 'results'))
            ->header('X-Robots-Tag', 'noindex')
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    })->name('search');

    Route::get('/write', [GuestConfessionController::class, 'create'])->middleware(PrivateResponseHeaders::class)->name('confessions.create');
    Route::post('/write', [GuestConfessionController::class, 'store'])->middleware(PrivateResponseHeaders::class)->name('confessions.store');
    Route::get('/my/{publicId}', [GuestConfessionController::class, 'status'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.status');
    Route::get('/my/{publicId}/recover', [GuestConfessionController::class, 'recoveryForm'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.recovery.form');
    Route::post('/my/{publicId}/recover', [GuestConfessionController::class, 'recover'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.recovery');

    Route::post('/my/{publicId}/withdraw', [GuestConfessionController::class, 'withdraw'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.withdraw');
    Route::post('/my/{publicId}/republish', [GuestConfessionController::class, 'requestRepublish'])
        ->where('publicId', '[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}')
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.republish');

    Route::get('/moderation/login', [ModeratorAuthController::class, 'loginForm'])->middleware(PrivateResponseHeaders::class)->name('moderation.login');
    Route::post('/moderation/login', [ModeratorAuthController::class, 'login'])->middleware(PrivateResponseHeaders::class)->name('moderation.login.submit');
    Route::get('/moderation/mfa/enroll', [ModeratorAuthController::class, 'enroll'])->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class])->name('moderation.mfa.enroll');
    Route::post('/moderation/mfa/confirm', [ModeratorAuthController::class, 'confirmEnrollment'])->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class])->name('moderation.mfa.confirm');
    Route::get('/moderation/mfa/challenge', [ModeratorAuthController::class, 'challenge'])->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class])->name('moderation.mfa.challenge');
    Route::post('/moderation/mfa/verify', [ModeratorAuthController::class, 'verifyChallenge'])->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class])->name('moderation.mfa.verify');
    Route::post('/moderation/logout', [ModeratorAuthController::class, 'logout'])->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class])->name('moderation.logout');

    Route::prefix('/moderation')->middleware([PrivateResponseHeaders::class, RequireModeratorAccount::class, RequireFreshModeratorMfa::class])->group(function (): void {
        Route::get('/', [ModerationController::class, 'index'])->name('moderation.queue');
        Route::get('/confessions/{confessionId}', [ModerationController::class, 'show'])->whereNumber('confessionId')->name('moderation.show');
        Route::post('/confessions/{confessionId}/decision', [ModerationController::class, 'decide'])->whereNumber('confessionId')->name('moderation.decide');

        Route::middleware(RequireModeratorAdmin::class)->group(function (): void {
            Route::get('/grants', [ModerationAdminController::class, 'index'])->name('moderation.grants');
            Route::post('/grants', [ModerationAdminController::class, 'store'])->name('moderation.grants.store');
            Route::post('/grants/{grantId}/revoke', [ModerationAdminController::class, 'revoke'])->whereNumber('grantId')->name('moderation.grants.revoke');
        });
    });
});

Route::get('/topic/{category}', function (string $category, Request $request, LocaleRegistry $locales) {
    abort_unless($locales->category($category), 404);
    $locale = $locales->resolveTag((string) $request->cookie('lavbe_locale'))
        ?? $locales->fromAcceptLanguage([(string) $request->header('Accept-Language', '')])
        ?? $locales->fallback();

    return response('', 301)->header('Location', route('topic.show', [
        'locale' => $locale['segment'],
        'category' => $category,
        'sort' => $request->query('sort', 'new'),
    ]));
})->name('topic.legacy');

Route::get('/_ui/admin-preview', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    return view('admin.preview');
})->name('admin.preview');

Route::get('/admin', function () {
    abort(404);
})->name('admin.placeholder');
