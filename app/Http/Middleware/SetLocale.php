<?php

namespace App\Http\Middleware;

use App\Support\Lavbe\LocaleRegistry;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(private readonly LocaleRegistry $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->bySegment((string) $request->route('locale'));
        abort_unless($locale, 404);

        app()->setLocale($locale['code']);
        URL::defaults(['locale' => $locale['segment']]);
        $request->attributes->set('lavbe.locale', $locale);
        app('view')->share('lavbeLocale', $locale);

        return $next($request);
    }
}
