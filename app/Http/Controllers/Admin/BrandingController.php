<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function index(): View
    {
        return view('admin.branding.index', [
            'logoUrl' => SiteBranding::logoUrl(),
            'faviconUrl' => SiteBranding::faviconUrl(),
            'customName' => SiteBranding::customName(),
            'customTagline' => SiteBranding::customTagline(),
            'homeH1' => SiteBranding::homeH1(),
            'homeSubH1' => SiteBranding::homeSubH1(),
            'heroSubtitle' => SiteBranding::heroSubtitle(),
            'footerTagline' => SiteBranding::footerTagline(),
            'footerSubTagline' => SiteBranding::footerSubTagline(),
            'footerDescriptionTagline' => SiteBranding::footerDescriptionTagline(),
            'contactEmail' => SiteBranding::contactEmail(),
            'privacyEmail' => SiteBranding::privacyEmail(),
            'socialUrls' => SiteBranding::socialUrls(),
            'networks' => SiteBranding::networks(),
            'defaultContactEmail' => config('site.contact_email'),
            'defaultPrivacyEmail' => config('site.privacy_email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $networkKeys = array_keys(SiteBranding::networks());

        $rules = [
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'remove_logo' => ['nullable', 'boolean'],
            'favicon_file' => ['nullable', 'image', 'max:4096'],
            'remove_favicon' => ['nullable', 'boolean'],
            'site_name' => ['nullable', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'home_h1' => ['nullable', 'string', 'max:180'],
            'home_sub_h1' => ['nullable', 'string', 'max:200'],
            'hero_subtitle' => ['nullable', 'string', 'max:320'],
            'footer_tagline' => ['nullable', 'string', 'max:200'],
            'footer_sub_tagline' => ['nullable', 'string', 'max:200'],
            'footer_description_tagline' => ['nullable', 'string', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'privacy_email' => ['nullable', 'email', 'max:255'],
        ];

        foreach ($networkKeys as $key) {
            $rules["social.{$key}"] = ['nullable', 'url', 'max:500'];
        }

        $validated = $request->validate($rules);

        if ($request->boolean('remove_logo')) {
            SiteBranding::removeLogo();
        } elseif ($request->hasFile('logo_file')) {
            SiteBranding::setLogoFromUpload($request->file('logo_file'));
        } elseif (filled($validated['logo_url'] ?? null)) {
            SiteBranding::setLogoFromUrl($validated['logo_url']);
        }

        if ($request->boolean('remove_favicon')) {
            SiteBranding::removeFavicon();
        } elseif ($request->hasFile('favicon_file')) {
            SiteBranding::setFaviconFromUpload($request->file('favicon_file'));
        }

        SiteBranding::setSocialUrls($validated['social'] ?? []);
        SiteBranding::setCustomName($validated['site_name'] ?? '');
        SiteBranding::setCustomTagline($validated['site_tagline'] ?? '');
        SiteBranding::setHomeH1($validated['home_h1'] ?? '');
        SiteBranding::setHomeSubH1($validated['home_sub_h1'] ?? '');
        SiteBranding::setHeroSubtitle($validated['hero_subtitle'] ?? '');
        SiteBranding::setFooterTagline($validated['footer_tagline'] ?? '');
        SiteBranding::setFooterSubTagline($validated['footer_sub_tagline'] ?? '');
        SiteBranding::setFooterDescriptionTagline($validated['footer_description_tagline'] ?? '');
        SiteBranding::setContactEmail($validated['contact_email'] ?? '');
        SiteBranding::setPrivacyEmail($validated['privacy_email'] ?? '');

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Branding, favicon, homepage copy, emails, and social links saved.');
    }
}
