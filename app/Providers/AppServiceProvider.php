<?php

namespace App\Providers;

use App\Support\Lavbe\LocaleRegistry;
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
        View::composer(['errors::404', 'components.site-header', 'components.site-navigation'], function ($view): void {
            $registry = app(LocaleRegistry::class);
            $segment = request()->route('locale') ?? request()->segment(1);
            $locale = $registry->bySegment((string) $segment) ?? $registry->fallback();
            if ($view->name() === 'errors::404') {
                app()->setLocale($locale['code']);
            }
            $view->with('lavbeLocale', $locale);
        });
    }
}
