<?php

namespace App\Providers;

use App\Support\Lavbe\LocaleRegistry;
use App\Support\Push\MinishlinkWebPushGateway;
use App\Support\Push\WebPushGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LocaleRegistry::class);
        $this->app->bind(WebPushGateway::class, MinishlinkWebPushGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(fn ($user): string => URL::temporarySignedRoute(
            'verification.verify', now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['locale' => $user->preferred_locale ?: 'en', 'id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        ));
        ResetPassword::createUrlUsing(fn ($user, string $token): string => URL::route('password.reset', [
            'locale' => $user->preferred_locale ?: 'en', 'token' => $token, 'email' => $user->getEmailForPasswordReset(),
        ]));
        View::composer('components.site-header', function ($view): void {
            $locale = app(LocaleRegistry::class)->bySegment((string) request()->route('locale'))
                ?? app(LocaleRegistry::class)->fallback();
            $view->with('lavbeLocale', $locale);
        });
        View::composer('components.site-navigation', function ($view): void {
            $locale = app(LocaleRegistry::class)->bySegment((string) request()->route('locale'))
                ?? app(LocaleRegistry::class)->fallback();
            $view->with('lavbeLocale', $locale);
        });
    }
}
