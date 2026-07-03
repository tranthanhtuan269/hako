<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteIntegrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function index(): View
    {
        return view('admin.integrations.index', [
            'scanSite' => SiteIntegrations::scanSite(),
            'geminiMasked' => SiteIntegrations::maskedGeminiApiKey(),
            'geminiConfigured' => filled(SiteIntegrations::geminiApiKey()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'scan_site' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z0-9_-]*$/'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'clear_gemini_api_key' => ['nullable', 'boolean'],
        ]);

        SiteIntegrations::setScanSite($validated['scan_site'] ?? '');

        if ($request->boolean('clear_gemini_api_key')) {
            SiteIntegrations::clearGeminiApiKey();
        } elseif (filled($validated['gemini_api_key'] ?? null)) {
            SiteIntegrations::setGeminiApiKey($validated['gemini_api_key']);
        }

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Integration settings saved.');
    }
}
