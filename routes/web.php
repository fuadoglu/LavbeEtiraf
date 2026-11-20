<?php

use App\Http\Controllers\AccountAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppealController;
use App\Http\Controllers\GuestConfessionController;
use App\Http\Controllers\IndexNowKeyController;
use App\Http\Controllers\ModerationAdminController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ModeratorAuthController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\PublicConfessionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\PrivateResponseHeaders;
use App\Http\Middleware\RequireFreshModeratorMfa;
use App\Http\Middleware\RequireModeratorAccount;
use App\Http\Middleware\RequireModeratorAdmin;
use App\Http\Middleware\SetLocale;
use App\Support\Lavbe\FeedCursor;
use App\Support\Lavbe\FeedQuery;
use App\Support\Lavbe\LocaleRegistry;
use App\Support\Lavbe\RoutePatterns;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::pattern('publicId', RoutePatterns::UUID_V4);
Route::pattern('targetPublicId', RoutePatterns::UUID_V4);
Route::pattern('commentPublicId', RoutePatterns::UUID_V4);
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/{key}.txt', IndexNowKeyController::class)->where('key', '[A-Za-z0-9-]{8,128}')->name('indexnow.key');

Route::get('/', function (Request $request, LocaleRegistry $locales) {
    $locale = $locales->resolveTag((string) $request->cookie('lavbe_locale'))
        ?? $locales->fromAcceptLanguage([(string) $request->header('Accept-Language', '')])
        ?? $locales->fromCountry($request->header('CF-IPCountry'), (string) $request->header('Accept-Language', ''))
        ?? $locales->fallback();

    return redirect()->route('home', ['locale' => $locale['segment']], 302);
})->name('root');

Route::prefix('{locale}')->where(['locale' => '[a-z]{2}(?:-[a-z]{2})?'])->middleware(SetLocale::class)->group(function (): void {
    $routeThrottle = static function (string $key): string {
        $limit = config('lavbe.rate_limits.routes.'.$key);

        return 'throttle:'.$limit['attempts'].','.$limit['decay_minutes'];
    };

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

        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate((int) config('lavbe.pagination.feed'))->withQueryString();

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

    Route::get('/confession/{publicId}', [PublicConfessionController::class, 'show'])->name('confessions.public');
    Route::get('/report/{type}/{targetPublicId}', [ReportController::class, 'create'])
        ->whereIn('type', RoutePatterns::REPORT_TARGET_TYPES)
        ->middleware(PrivateResponseHeaders::class)->name('reports.create');
    Route::get('/legal/report/{type}/{targetPublicId}', [ReportController::class, 'legalNotice'])
        ->whereIn('type', RoutePatterns::REPORT_TARGET_TYPES)
        ->middleware(PrivateResponseHeaders::class)->name('reports.legal.create');
    Route::post('/report/{channel}/{type}/{targetPublicId}', [ReportController::class, 'store'])
        ->whereIn('channel', RoutePatterns::REPORT_CHANNELS)->whereIn('type', RoutePatterns::REPORT_TARGET_TYPES)
        ->middleware(PrivateResponseHeaders::class)->name('reports.store');
    Route::get('/appeal/{type}/{targetPublicId}', [AppealController::class, 'create'])
        ->whereIn('type', RoutePatterns::REPORT_TARGET_TYPES)
        ->middleware(PrivateResponseHeaders::class)->name('appeals.create');
    Route::post('/appeal/{type}/{targetPublicId}', [AppealController::class, 'store'])
        ->whereIn('type', RoutePatterns::REPORT_TARGET_TYPES)
        ->middleware(PrivateResponseHeaders::class)->name('appeals.store');
    Route::post('/confession/{publicId}/comments', [ParticipationController::class, 'comment'])->middleware(PrivateResponseHeaders::class)->name('confessions.comments.store');
    Route::post('/confession/{publicId}/comments/{commentPublicId}/response', [ParticipationController::class, 'respond'])->middleware(PrivateResponseHeaders::class)->name('confessions.comment.response');
    Route::post('/confession/{publicId}/continuations', [ParticipationController::class, 'continuation'])->middleware(PrivateResponseHeaders::class)->name('confessions.continuations.store');
    Route::post('/confession/{publicId}/support', [ParticipationController::class, 'support'])->middleware(PrivateResponseHeaders::class)->name('confessions.support');
    Route::post('/my/{publicId}/reviews', [ParticipationController::class, 'reviews'])->middleware(PrivateResponseHeaders::class)->name('confessions.reviews');
    Route::get('/comment/{commentPublicId}', [ParticipationController::class, 'commentStatus'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.status');
    Route::get('/comment/{commentPublicId}/recover', [ParticipationController::class, 'recoveryForm'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.recovery.form');
    Route::post('/comment/{commentPublicId}/recover', [ParticipationController::class, 'recover'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.recovery');
    Route::post('/comment/{commentPublicId}/withdraw', [ParticipationController::class, 'withdrawComment'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.withdraw');

    Route::post('/language', function (string $localeSegment, Request $request, LocaleRegistry $locales) {
        abort_unless($locales->bySegment($localeSegment), 404);
        $validated = $request->validate(['locale' => ['required', 'string', 'max:'.config('lavbe.limits.locale_segment')]]);
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

        return $response->withCookie(cookie('lavbe_locale', $locale['segment'], (int) config('lavbe.cookies.locale_minutes'), '/', null, $request->isSecure(), true, false, 'lax'));
    })->name('locale.select');

    Route::get('/account/register', [AccountAuthController::class, 'registerForm'])->middleware([PrivateResponseHeaders::class, 'guest'])->name('account.register');
    Route::post('/account/register', [AccountAuthController::class, 'register'])->middleware([PrivateResponseHeaders::class, 'guest', $routeThrottle('account_register')])->name('account.register.store');
    Route::get('/account/login', [AccountAuthController::class, 'loginForm'])->middleware([PrivateResponseHeaders::class, 'guest'])->name('account.login');
    Route::post('/account/login', [AccountAuthController::class, 'login'])->middleware([PrivateResponseHeaders::class, 'guest', $routeThrottle('account_login')])->name('account.login.store');
    Route::get('/account/email/verify', [AccountAuthController::class, 'verificationNotice'])->middleware([PrivateResponseHeaders::class, 'auth'])->name('verification.notice');
    Route::get('/account/email/verify/{id}/{hash}', [AccountAuthController::class, 'verify'])->whereNumber('id')->middleware([PrivateResponseHeaders::class, 'auth', 'signed', $routeThrottle('email_verify')])->name('verification.verify');
    Route::post('/account/email/verification-notification', [AccountAuthController::class, 'resendVerification'])->middleware([PrivateResponseHeaders::class, 'auth', $routeThrottle('email_resend')])->name('verification.send');
    Route::get('/account/password/forgot', [AccountAuthController::class, 'forgotForm'])->middleware([PrivateResponseHeaders::class, 'guest'])->name('password.request');
    Route::post('/account/password/forgot', [AccountAuthController::class, 'forgot'])->middleware([PrivateResponseHeaders::class, 'guest', $routeThrottle('password_email')])->name('password.email');
    Route::get('/account/password/reset/{token}', [AccountAuthController::class, 'resetForm'])->middleware([PrivateResponseHeaders::class, 'guest'])->name('password.reset');
    Route::post('/account/password/reset', [AccountAuthController::class, 'reset'])->middleware([PrivateResponseHeaders::class, 'guest', $routeThrottle('password_update')])->name('password.update');
    Route::post('/account/logout', [AccountAuthController::class, 'logout'])->middleware([PrivateResponseHeaders::class, 'auth'])->name('account.logout');
    Route::middleware(['auth', 'verified', PrivateResponseHeaders::class])->group(function (): void {
        Route::get('/my', [AccountController::class, 'home'])->name('account.home');
        Route::put('/my/preferences', [AccountController::class, 'preferences'])->name('account.preferences');
        Route::post('/my/follows/{publicId}', [AccountController::class, 'follow'])->name('account.follow');
        Route::delete('/my/follows/{publicId}', [AccountController::class, 'unfollow'])->name('account.unfollow');
        Route::post('/my/notifications/{notificationId}/read', [AccountController::class, 'readNotification'])->whereNumber('notificationId')->name('account.notifications.read');
        Route::post('/my/{publicId}/claim', [AccountController::class, 'claimConfession'])->name('account.confessions.claim');
        Route::post('/my/comments/{publicId}/claim', [AccountController::class, 'claimComment'])->name('account.comments.claim');
    });

    Route::get('/topics/{category}', function (string $locale, string $category, Request $request, LocaleRegistry $locales, FeedQuery $feed) {
        abort_unless($locales->bySegment($locale), 404);
        abort_unless($locales->category($category), 404);
        $sort = $request->query('sort', 'new');
        abort_unless(in_array($sort, array_keys(config('lavbe.feed_orders')), true), 404);

        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate((int) config('lavbe.pagination.feed'))->withQueryString();

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
        $pageSize = (int) config('lavbe.pagination.feed');
        $items = $feed->build(app()->getLocale(), $category, $order)->skip($position)->take($pageSize + (int) config('lavbe.pagination.feed_cursor_overfetch'))->get();
        $hasMore = $items->count() > $pageSize;
        $items = $items->take($pageSize);
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
        abort_if(mb_strlen($query, 'UTF-8') > (int) config('lavbe.limits.search_query'), 422);
        $results = null;
        if ($query !== '') {
            $results = $feed->build(app()->getLocale(), null, 'new')
                ->where(function ($builder) use ($query): void {
                    $builder->where('title', 'like', '%'.$query.'%')->orWhere('body', 'like', '%'.$query.'%');
                })->paginate((int) config('lavbe.pagination.feed'));
        }

        return response()->view('search', compact('query', 'results'))
            ->header('X-Robots-Tag', 'noindex')
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    })->name('search');

    Route::get('/write', [GuestConfessionController::class, 'create'])->middleware(PrivateResponseHeaders::class)->name('confessions.create');
    Route::post('/write', [GuestConfessionController::class, 'store'])->middleware(PrivateResponseHeaders::class)->name('confessions.store');
    Route::get('/my/{publicId}', [GuestConfessionController::class, 'status'])
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.status');
    Route::get('/my/{publicId}/recover', [GuestConfessionController::class, 'recoveryForm'])
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.recovery.form');
    Route::post('/my/{publicId}/recover', [GuestConfessionController::class, 'recover'])
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.recovery');

    Route::post('/my/{publicId}/withdraw', [GuestConfessionController::class, 'withdraw'])
        ->middleware(PrivateResponseHeaders::class)
        ->name('confessions.withdraw');
    Route::post('/my/{publicId}/republish', [GuestConfessionController::class, 'requestRepublish'])
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
        Route::get('/participation', [ModerationController::class, 'participation'])->name('moderation.participation');
        Route::get('/reports', [ModerationController::class, 'reports'])->name('moderation.reports');
        Route::get('/reports/{groupId}', [ModerationController::class, 'report'])->whereNumber('groupId')->name('moderation.reports.show');
        Route::post('/reports/{groupId}/decision', [ModerationController::class, 'decideReport'])->whereNumber('groupId')->name('moderation.reports.decide');
        Route::get('/appeals', [ModerationController::class, 'appeals'])->name('moderation.appeals');
        Route::get('/appeals/{appealId}', [ModerationController::class, 'appeal'])->whereNumber('appealId')->name('moderation.appeals.show');
        Route::post('/appeals/{appealId}/decision', [ModerationController::class, 'decideAppeal'])->whereNumber('appealId')->name('moderation.appeals.decide');
        Route::post('/participation/{type}/{targetId}/decision', [ModerationController::class, 'decideParticipation'])
            ->where(['type' => 'comment|response|continuation', 'targetId' => '[0-9]+'])
            ->name('moderation.participation.decide');
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
