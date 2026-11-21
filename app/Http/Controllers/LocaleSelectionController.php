<?php

namespace App\Http\Controllers;

use App\Support\Http\ResponseHeaders;
use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LocaleSelectionController extends Controller
{
    private const RETURN_ROUTES = [
        'home', 'topic.show', 'search', 'confessions.create', 'confessions.supported',
        'confessions.device', 'legal.page', 'account.home', 'account.login',
        'account.register', 'password.request', 'verification.notice',
        'moderation.login', 'moderation.queue', 'moderation.participation',
        'moderation.reports', 'moderation.appeals', 'moderation.grants',
    ];

    public function __invoke(Request $request, string $locale, LocaleRegistry $locales): Response
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:'.config('lavbe.limits.locale_segment')],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);
        $selected = $locales->bySegment($validated['locale']);
        abort_unless($selected, 422);

        $destination = $this->destination($validated['return_to'] ?? '', $locale, $selected['segment'], $locales);

        return response('', 303)
            ->header('Location', $destination)
            ->header('Cache-Control', ResponseHeaders::CACHE_PRIVATE_NO_STORE)
            ->withCookie(cookie('lavbe_locale', $selected['segment'], (int) config('lavbe.cookies.locale_minutes'), '/', null, $request->isSecure(), true, false, 'lax'));
    }

    private function destination(string $returnTo, string $current, string $selected, LocaleRegistry $locales): string
    {
        $fallback = route('home', ['locale' => $selected], false);
        $parts = parse_url($returnTo);
        $path = $parts['path'] ?? '';
        if (! preg_match('#^/[a-z0-9/-]+$#D', $path)
            || isset($parts['host']) || isset($parts['scheme'])
            || str_starts_with($returnTo, '//')
            || ! str_starts_with($path.'/', '/'.$current.'/')) {
            return $fallback;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($path));
        } catch (HttpExceptionInterface) {
            return $fallback;
        }
        $name = $route->getName();
        if (! in_array($name, self::RETURN_ROUTES, true)) {
            return $fallback;
        }

        $parameters = ['locale' => $selected] + $route->parameters();
        if ($name === 'topic.show' && ! $locales->category($parameters['category'])) {
            return $fallback;
        }
        if (in_array($name, ['home', 'topic.show'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (is_string($query['sort'] ?? null) && array_key_exists($query['sort'], config('lavbe.feed_orders'))) {
                $parameters['sort'] = $query['sort'];
            }
            if ($current === $selected && is_string($query['page'] ?? null)
                && filter_var($query['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]) !== false) {
                $parameters['page'] = $query['page'];
            }
        }

        return route($name, $parameters, false);
    }
}
