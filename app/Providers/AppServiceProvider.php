<?php

namespace App\Providers;

use App\Support\SiteBranding;
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

        try {
            $siteLogoUrl = SiteBranding::logoUrl();
            $siteSocialLinks = SiteBranding::socialLinks();
        } catch (\Throwable) {
            $siteLogoUrl = null;
            $siteSocialLinks = [];
        }

        View::share([
            'siteName' => config('site.name'),
            'siteUrl' => rtrim(config('site.url'), '/'),
            'siteDomain' => config('site.domain'),
            'contactEmail' => config('site.contact_email'),
            'privacyEmail' => config('site.privacy_email'),
            'lastUpdated' => config('site.legal_last_updated'),
            'activeTheme' => ThemeManager::current(),
            'siteLogoUrl' => $siteLogoUrl,
            'siteSocialLinks' => $siteSocialLinks,
        ]);
    }
}
