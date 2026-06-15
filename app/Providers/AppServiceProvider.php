<?php

namespace App\Providers;

use App\Support\ThemeManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::share([
            'siteName' => config('site.name'),
            'siteUrl' => rtrim(config('site.url'), '/'),
            'siteDomain' => config('site.domain'),
            'contactEmail' => config('site.contact_email'),
            'privacyEmail' => config('site.privacy_email'),
            'lastUpdated' => config('site.legal_last_updated'),
            'activeTheme' => ThemeManager::current(),
        ]);
    }
}
