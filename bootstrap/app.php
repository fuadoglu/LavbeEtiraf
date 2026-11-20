<?php

use App\Support\Lavbe\LocaleRegistry;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request) => route('account.login', ['locale' => $request->route('locale') ?? app(LocaleRegistry::class)->fallback()['segment']]));
        $middleware->redirectUsersTo(fn (Request $request) => route('account.home', ['locale' => $request->route('locale') ?? app(LocaleRegistry::class)->fallback()['segment']]));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
