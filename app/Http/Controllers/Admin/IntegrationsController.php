<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteCouponRedirect;
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
            'geminiMasked' => SiteIntegrations::maskedGeminiApiKey(),
            'geminiConfigured' => filled(SiteIntegrations::geminiApiKey()),
            'allowReimportExistingStores' => SiteImportSettings::allowReimportExistingStores(),
            'couponRedirectFlow' => SiteCouponRedirect::flow(),
            'couponRedirectOptions' => SiteCouponRedirect::options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'clear_gemini_api_key' => ['nullable', 'boolean'],
            'import_allow_reimport_existing_stores' => ['nullable', 'boolean'],
            'coupon_redirect_flow' => ['required', 'in:close,copy,both'],
        ]);

        if ($request->boolean('clear_gemini_api_key')) {
            SiteIntegrations::clearGeminiApiKey();
        } elseif (filled($validated['gemini_api_key'] ?? null)) {
            SiteIntegrations::setGeminiApiKey($validated['gemini_api_key']);
        }

        SiteImportSettings::setAllowReimportExistingStores(
            $request->boolean('import_allow_reimport_existing_stores')
        );

        SiteCouponRedirect::setFlow($validated['coupon_redirect_flow']);

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Integration settings saved.');
    }
}
