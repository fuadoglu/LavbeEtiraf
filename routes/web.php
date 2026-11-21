<?php

use App\Http\Controllers\AccountAuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppealController;
use App\Http\Controllers\GuestConfessionController;
use App\Http\Controllers\IndexNowKeyController;
use App\Http\Controllers\LocaleSelectionController;
use App\Http\Controllers\ModerationAdminController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ModeratorAuthController;
use App\Http\Controllers\ParticipationController;
use App\Http\Controllers\PublicConfessionController;
use App\Http\Controllers\PwaManifestController;
use App\Http\Controllers\ReaderConsentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Middleware\NormalizeFeedPage;
use App\Http\Middleware\PrivateResponseHeaders;
use App\Http\Middleware\RequireFreshModeratorMfa;
use App\Http\Middleware\RequireModeratorAccount;
use App\Http\Middleware\RequireModeratorAdmin;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ValidatePaginationParameters;
use App\Support\Http\ResponseHeaders;
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
Route::get('/{key}.txt', IndexNowKeyController::class)->where('key', RoutePatterns::INDEXNOW_KEY)->name('indexnow.key');

Route::get('/', function (Request $request, LocaleRegistry $locales) {
    $locale = $locales->resolveTag((string) $request->cookie('lavbe_locale'))
        ?? $locales->fromAcceptLanguage([(string) $request->header('Accept-Language', '')])
        ?? $locales->fromCountry($request->header('CF-IPCountry'), (string) $request->header('Accept-Language', ''))
        ?? $locales->fallback();

    return redirect()->route('home', ['locale' => $locale['segment']], 302);
})->name('root');

Route::prefix('{locale}')->where(['locale' => RoutePatterns::LOCALE_SEGMENT])->middleware([SetLocale::class, ValidatePaginationParameters::class])->group(function (): void {
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
            ->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en'], 'icon' => $item['icon']])
            ->all();

        $pageSize = (int) config('feed.page_size');
        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate($pageSize)->withQueryString();
        abort_if($confessions->currentPage() > $confessions->lastPage(), 404);

        $canonical = route('home', ['locale' => $locale], false).($confessions->currentPage() > 1 ? '?page='.$confessions->currentPage() : '');

        return view('home', [
            'category' => $category,
            'sort' => $sort,
            'locale' => $request->attributes->get('lavbe.locale'),
            'categories' => $categories,
            'confessions' => $confessions,
            'canonical' => $canonical,
            'categoryIndexable' => $category === null || $confessions->total() >= (int) config('seo.category_min_public_items'),
        ]);
    })->middleware(NormalizeFeedPage::class)->name('home');

    Route::get('/supported', [ParticipationController::class, 'supported'])->middleware(PrivateResponseHeaders::class)->name('confessions.supported');
    Route::get('/manifest.webmanifest', [PwaManifestController::class, 'show'])->name('pwa.manifest');
    Route::post('/reader-consent', [ReaderConsentController::class, 'update'])->middleware(PrivateResponseHeaders::class)->name('reader-consent.update');
    Route::get('/my/device', [GuestConfessionController::class, 'deviceIndex'])->middleware(PrivateResponseHeaders::class)->name('confessions.device');
    Route::post('/my/device/forget', [GuestConfessionController::class, 'forgetDevice'])->middleware(PrivateResponseHeaders::class)->name('confessions.device.forget');
    Route::get('/legal/{page}', function (string $locale, string $page) {
        $copy = __('legal.'.$page);
        abort_unless(is_array($copy) && isset($copy['title'], $copy['body']), 404);
        $copy['body'] = str_replace(
            [':retention_days', ':site_name', ':site_domain'],
            [(string) config('analytics.unique_read_retention_days'), (string) config('app.name'), (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: config('app.name'))],
            $copy['body'],
        );

        return response()->view('legal.draft', ['copy' => $copy])
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_PRIVATE);
    })->whereIn('page', ['terms', 'community', 'privacy', 'cookies', 'illegal_content', 'data_request', 'operator'])->name('legal.page');

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
    Route::get('/confession/{publicId}/comment', [ParticipationController::class, 'commentForm'])->middleware(PrivateResponseHeaders::class)->name('confessions.comments.create');
    Route::post('/confession/{publicId}/comments', [ParticipationController::class, 'comment'])->middleware(PrivateResponseHeaders::class)->name('confessions.comments.store');
    Route::post('/confession/{publicId}/comments/{commentPublicId}/reaction', [ParticipationController::class, 'commentReaction'])->middleware(PrivateResponseHeaders::class)->name('confessions.comments.reaction');
    Route::post('/confession/{publicId}/comments/{commentPublicId}/response', [ParticipationController::class, 'respond'])->middleware(PrivateResponseHeaders::class)->name('confessions.comment.response');
    Route::post('/confession/{publicId}/continuations', [ParticipationController::class, 'continuation'])->middleware(PrivateResponseHeaders::class)->name('confessions.continuations.store');
    Route::post('/confession/{publicId}/support', [ParticipationController::class, 'support'])->middleware(PrivateResponseHeaders::class)->name('confessions.support');
    Route::post('/my/{publicId}/reviews', [ParticipationController::class, 'reviews'])->middleware(PrivateResponseHeaders::class)->name('confessions.reviews');
    Route::get('/comment/{commentPublicId}', [ParticipationController::class, 'commentStatus'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.status');
    Route::get('/comment/{commentPublicId}/recover', [ParticipationController::class, 'recoveryForm'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.recovery.form');
    Route::post('/comment/{commentPublicId}/recover', [ParticipationController::class, 'recover'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.recovery');
    Route::post('/comment/{commentPublicId}/withdraw', [ParticipationController::class, 'withdrawComment'])->middleware(PrivateResponseHeaders::class)->name('participation.comment.withdraw');

    Route::post('/language', LocaleSelectionController::class)->name('locale.select');

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

        $pageSize = (int) config('feed.page_size');
        $confessions = $feed->build(app()->getLocale(), $category, $sort)->paginate($pageSize)->withQueryString();
        abort_if($confessions->currentPage() > $confessions->lastPage(), 404);

        $canonical = route('topic.show', ['locale' => $locale, 'category' => $category], false).($confessions->currentPage() > 1 ? '?page='.$confessions->currentPage() : '');

        return view('home', [
            'category' => $category,
            'sort' => $sort,
            'locale' => $request->attributes->get('lavbe.locale'),
            'categories' => collect($locales->catalog()['categories'])
                ->map(fn (array $item): array => ['key' => $item['key'], 'label' => $item['labels'][app()->getLocale()] ?? $item['labels']['en'], 'icon' => $item['icon']])
                ->all(),
            'confessions' => $confessions,
            'canonical' => $canonical,
            'categoryIndexable' => $confessions->total() >= (int) config('seo.category_min_public_items'),
        ]);
    })->middleware(NormalizeFeedPage::class)->name('topic.show');

    Route::match(['GET', 'POST'], '/search', function (string $locale, Request $request, LocaleRegistry $locales, FeedQuery $feed) {
        abort_unless($locales->bySegment($locale), 404);
        $requestedPage = $request->input('page', 1);
        abort_unless((is_string($requestedPage) || is_int($requestedPage))
            && preg_match('/^[1-9][0-9]*$/D', (string) $requestedPage) === 1
            && filter_var($requestedPage, FILTER_VALIDATE_INT) !== false, 404);
        $submittedQuery = $request->isMethod('post') ? $request->input('q', '') : '';
        abort_unless(is_string($submittedQuery), 422);
        $query = trim($submittedQuery);
        abort_if(mb_strlen($query, 'UTF-8') > (int) config('lavbe.limits.search_query'), 422);
        $results = null;
        if ($query !== '') {
            $literalQuery = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
            $pattern = '%'.$literalQuery.'%';
            $results = $feed->build(app()->getLocale(), null, 'new')
                ->where(function ($builder) use ($pattern): void {
                    $builder->whereRaw("confessions.title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("confessions.body LIKE ? ESCAPE '!'", [$pattern]);
                })->paginate((int) config('feed.page_size'), ['*'], 'page', (int) $requestedPage);
            abort_if($results->currentPage() > $results->lastPage(), 404);
        }

        return response()->view('search', compact('query', 'results'))
            ->header('X-Robots-Tag', ResponseHeaders::ROBOTS_NO_INDEX)
            ->header('Cache-Control', ResponseHeaders::CACHE_NO_STORE_PRIVATE)
            ->header('Referrer-Policy', ResponseHeaders::REFERRER_NO_REFERRER);
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
            Route::match(['GET', 'POST'], '/grants', [ModerationAdminController::class, 'index'])->name('moderation.grants');
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
