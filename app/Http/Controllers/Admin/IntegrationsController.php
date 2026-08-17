<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AiProviderCatalog;
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
        $providers = [];

        foreach (AiProviderCatalog::all() as $id => $meta) {
            $configured = filled(SiteIntegrations::aiApiKey($id));
            $providers[] = [
                'id' => $id,
                'label' => $meta['label'],
                'hint' => $meta['hint'],
                'docs_url' => $meta['docs_url'],
                'model_hint' => $meta['model_hint'],
                'default_model' => $meta['default_model'],
                'model' => SiteIntegrations::aiModel($id),
                'configured' => $configured,
                'masked' => $configured ? SiteIntegrations::maskedAiApiKey($id) : '',
            ];
        }

        return view('admin.integrations.index', [
            'aiProvider' => SiteIntegrations::aiProvider(),
            'aiProviders' => $providers,
            'allowReimportExistingStores' => SiteImportSettings::allowReimportExistingStores(),
            'couponRedirectFlow' => SiteCouponRedirect::flow(),
            'couponRedirectOptions' => SiteCouponRedirect::options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $providerIds = AiProviderCatalog::ids();

        $validated = $request->validate([
            'ai_provider' => ['required', 'in:'.implode(',', $providerIds)],
            'ai_keys' => ['nullable', 'array'],
            'ai_keys.*' => ['nullable', 'string', 'max:500'],
            'ai_models' => ['nullable', 'array'],
            'ai_models.*' => ['nullable', 'string', 'max:120'],
            'clear_ai_keys' => ['nullable', 'array'],
            'clear_ai_keys.*' => ['nullable', 'boolean'],
            'import_allow_reimport_existing_stores' => ['nullable', 'boolean'],
            'coupon_redirect_flow' => ['required', 'in:close,copy,both'],
        ]);

        SiteIntegrations::setAiProvider($validated['ai_provider']);

        foreach ($providerIds as $id) {
            if ($request->boolean("clear_ai_keys.{$id}")) {
                SiteIntegrations::clearAiApiKey($id);
            } elseif (filled($validated['ai_keys'][$id] ?? null)) {
                SiteIntegrations::setAiApiKey($id, $validated['ai_keys'][$id]);
            }

            if (array_key_exists($id, $validated['ai_models'] ?? [])) {
                SiteIntegrations::setAiModel($id, $validated['ai_models'][$id]);
            }
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
