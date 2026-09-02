<?php

namespace App\Providers;

use App\Models\NavLink;
use App\Support\HeaderSearch;
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
            $siteFaviconUrl = SiteBranding::faviconUrl();
            $siteSocialLinks = SiteBranding::socialLinks();
            $siteBrandShowText = SiteBranding::hasCustomBrandText();
            $siteName = SiteBranding::resolvedName();
            $siteTagline = SiteBranding::resolvedTagline();
            $siteDisplayName = SiteBranding::customName();
            $siteDisplayTagline = SiteBranding::customTagline();
            $siteHomeH1 = SiteBranding::resolvedHomeH1();
            $siteHomeSubH1 = SiteBranding::resolvedHomeSubH1();
            $siteHeroSubtitle = SiteBranding::resolvedHeroSubtitle();
            $siteFooterTagline = SiteBranding::resolvedFooterTagline();
            $siteFooterSubTagline = SiteBranding::resolvedFooterSubTagline();
            $siteFooterDescriptionTagline = SiteBranding::resolvedFooterDescriptionTagline();
            $contactEmail = SiteBranding::resolvedContactEmail();
            $privacyEmail = SiteBranding::resolvedPrivacyEmail();
        } catch (\Throwable) {
            $siteLogoUrl = null;
            $siteFaviconUrl = null;
            $siteSocialLinks = [];
            $siteBrandShowText = false;
            $siteName = config('site.name');
            $siteTagline = config('site.tagline');
            $siteDisplayName = null;
            $siteDisplayTagline = null;
            $siteHomeH1 = config('site.name');
            $siteHomeSubH1 = config('site.tagline');
            $siteHeroSubtitle = 'Deals for brands like Amazon, Walmart, Target, and other U.S. retailers. '
                .config('site.name').' is not affiliated with these merchants.';
            $siteFooterTagline = config('site.tagline');
            $siteFooterSubTagline = config('site.tagline');
            $siteFooterDescriptionTagline = config('site.tagline');
            $contactEmail = config('site.contact_email');
            $privacyEmail = config('site.privacy_email');
        }

        View::share([
            'siteName' => $siteName,
            'siteTagline' => $siteTagline,
            'siteHomeH1' => $siteHomeH1,
            'siteHomeSubH1' => $siteHomeSubH1,
            'siteHeroSubtitle' => $siteHeroSubtitle,
            'siteFooterTagline' => $siteFooterTagline,
            'siteFooterSubTagline' => $siteFooterSubTagline,
            'siteFooterDescriptionTagline' => $siteFooterDescriptionTagline,
            'siteBrandShowText' => $siteBrandShowText,
            'siteDisplayName' => $siteDisplayName,
            'siteDisplayTagline' => $siteDisplayTagline,
            'siteUrl' => rtrim(config('site.url'), '/'),
            'siteDomain' => config('site.domain'),
            'contactEmail' => $contactEmail,
            'privacyEmail' => $privacyEmail,
            'lastUpdated' => config('site.legal_last_updated'),
            'activeTheme' => ThemeManager::current(),
            'siteLogoUrl' => $siteLogoUrl,
            'siteFaviconUrl' => $siteFaviconUrl,
            'siteSocialLinks' => $siteSocialLinks,
            'headerNavLinks' => NavLink::headerItems(),
            'headerSearchVisible' => HeaderSearch::visible(),
        ]);
    }
}
