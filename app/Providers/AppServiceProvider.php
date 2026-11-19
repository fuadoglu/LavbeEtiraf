<?php

namespace App\Providers;

use App\Support\Lavbe\LocaleRegistry;
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
