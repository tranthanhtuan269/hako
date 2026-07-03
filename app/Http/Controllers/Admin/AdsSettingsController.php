<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SiteAdsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdsSettingsController extends Controller
{
    public function index(): View
    {
        $settings = SiteAdsSettings::get();

        if ($settings['sitelinks'] === []) {
            $settings['sitelinks'][] = [
                'link_text' => '',
                'final_url' => '',
                'description_1' => '',
                'description_2' => '',
                'status' => 'Enabled',
            ];
        }

        if ($settings['callouts'] === []) {
            $settings['callouts'][] = [
                'text' => '',
                'status' => 'Enabled',
            ];
        }

        return view('admin.ads-settings.index', [
            'settings' => $settings,
            'statuses' => SiteAdsSettings::STATUSES,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sitelinks' => ['nullable', 'array'],
            'sitelinks.*.link_text' => ['nullable', 'string', 'max:25'],
            'sitelinks.*.final_url' => ['nullable', 'url', 'max:500'],
            'sitelinks.*.description_1' => ['nullable', 'string', 'max:35'],
            'sitelinks.*.description_2' => ['nullable', 'string', 'max:35'],
            'sitelinks.*.status' => ['nullable', 'string', 'in:'.implode(',', SiteAdsSettings::STATUSES)],
            'callouts' => ['nullable', 'array'],
            'callouts.*.text' => ['nullable', 'string', 'max:25'],
            'callouts.*.status' => ['nullable', 'string', 'in:'.implode(',', SiteAdsSettings::STATUSES)],
        ]);

        SiteAdsSettings::save($validated);

        return redirect()
            ->route('admin.ads-settings.index')
            ->with('success', 'Google Ads settings saved.');
    }

    public function reset(): RedirectResponse
    {
        SiteAdsSettings::save(SiteAdsSettings::defaults());

        return redirect()
            ->route('admin.ads-settings.index')
            ->with('success', 'Google Ads settings reset to site defaults.');
    }
}
