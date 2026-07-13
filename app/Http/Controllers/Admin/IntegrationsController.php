<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteImportSettings;
use App\Support\SiteIntegrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index', [
            'scanSite' => SiteIntegrations::scanSiteStored(),
            'defaultScanSite' => SiteIntegrations::defaultScanSiteFromDomain(),
            'effectiveScanSite' => SiteIntegrations::scanSite(),
            'scanApiUrl' => SiteIntegrations::scanApiUrl(),
            'scanAffiliateSignupsApiUrl' => SiteIntegrations::scanAffiliateSignupsApiUrl(),
            'scanApiLimit' => SiteIntegrations::scanApiLimit(),
            'geminiMasked' => SiteIntegrations::maskedGeminiApiKey(),
            'geminiConfigured' => filled(SiteIntegrations::geminiApiKey()),
            'allowReimportExistingStores' => SiteImportSettings::allowReimportExistingStores(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scan_site' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]*$/'],
            'scan_api_url' => ['nullable', 'url', 'max:500'],
            'scan_affiliate_signups_api_url' => ['nullable', 'url', 'max:500'],
            'scan_api_limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'clear_gemini_api_key' => ['nullable', 'boolean'],
            'import_allow_reimport_existing_stores' => ['nullable', 'boolean'],
        ]);

        SiteIntegrations::setScanSite($validated['scan_site'] ?? '');
        SiteIntegrations::setScanApiUrl($validated['scan_api_url'] ?? '');
        SiteIntegrations::setScanAffiliateSignupsApiUrl($validated['scan_affiliate_signups_api_url'] ?? '');

        if (filled($validated['scan_api_limit'] ?? null)) {
            SiteIntegrations::setScanApiLimit((int) $validated['scan_api_limit']);
        }

        if ($request->boolean('clear_gemini_api_key')) {
            SiteIntegrations::clearGeminiApiKey();
        } elseif (filled($validated['gemini_api_key'] ?? null)) {
            SiteIntegrations::setGeminiApiKey($validated['gemini_api_key']);
        }

        SiteImportSettings::setAllowReimportExistingStores(
            $request->boolean('import_allow_reimport_existing_stores')
        );

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Integration settings saved.');
    }
}
