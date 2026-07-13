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
            'customName' => SiteBranding::customName(),
            'customTagline' => SiteBranding::customTagline(),
            'socialUrls' => SiteBranding::socialUrls(),
            'networks' => SiteBranding::networks(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $networkKeys = array_keys(SiteBranding::networks());

        $rules = [
            'logo_file' => ['nullable', 'image', 'max:2048'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'remove_logo' => ['nullable', 'boolean'],
            'site_name' => ['nullable', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
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

        SiteBranding::setSocialUrls($validated['social'] ?? []);
        SiteBranding::setCustomName($validated['site_name'] ?? '');
        SiteBranding::setCustomTagline($validated['site_tagline'] ?? '');

        return redirect()
            ->route('admin.branding.index')
            ->with('success', 'Site logo, name, slogan, and social links saved.');
    }
}
