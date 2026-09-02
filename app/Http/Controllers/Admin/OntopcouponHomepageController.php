<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OntopcouponHomepage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OntopcouponHomepageController extends Controller
{
    public function edit(): View
    {
        return view('admin.ontopcoupon-homepage.edit', [
            'homepage' => OntopcouponHomepage::resolved(),
            'sectionLabels' => [
                'hero' => 'Hero + search',
                'trust' => 'Trusted stores strip',
                'featured_coupons' => 'Featured coupons',
                'categories' => 'Categories',
                'stores' => 'Top stores',
                'how_it_works' => 'How it works',
                'featured_deal' => 'Featured deal sidebar',
                'blog' => 'Blog',
                'cta' => 'Shopper / owner CTAs',
                'newsletter' => 'Newsletter',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sections' => ['nullable', 'array'],
            'sections.*' => ['nullable'],
            'search_placeholder' => ['nullable', 'string', 'max:160'],
            'trust_label' => ['nullable', 'string', 'max:120'],
            'featured_coupons.kicker' => ['nullable', 'string', 'max:80'],
            'featured_coupons.title' => ['nullable', 'string', 'max:120'],
            'featured_coupons.link' => ['nullable', 'string', 'max:80'],
            'categories.kicker' => ['nullable', 'string', 'max:80'],
            'categories.title' => ['nullable', 'string', 'max:120'],
            'categories.link' => ['nullable', 'string', 'max:80'],
            'categories.explore' => ['nullable', 'string', 'max:40'],
            'stores.kicker' => ['nullable', 'string', 'max:80'],
            'stores.title' => ['nullable', 'string', 'max:120'],
            'stores.link' => ['nullable', 'string', 'max:80'],
            'how_it_works.kicker' => ['nullable', 'string', 'max:80'],
            'how_it_works.title' => ['nullable', 'string', 'max:160'],
            'how_it_works.cta' => ['nullable', 'string', 'max:80'],
            'how_it_works.steps' => ['nullable', 'array'],
            'how_it_works.steps.*.title' => ['nullable', 'string', 'max:120'],
            'how_it_works.steps.*.body' => ['nullable', 'string', 'max:400'],
            'featured_deal.kicker' => ['nullable', 'string', 'max:80'],
            'featured_deal.offers_label' => ['nullable', 'string', 'max:80'],
            'featured_deal.stores_label' => ['nullable', 'string', 'max:80'],
            'blog.kicker' => ['nullable', 'string', 'max:80'],
            'blog.title' => ['nullable', 'string', 'max:120'],
            'blog.link' => ['nullable', 'string', 'max:80'],
            'cta_shoppers.kicker' => ['nullable', 'string', 'max:80'],
            'cta_shoppers.title' => ['nullable', 'string', 'max:160'],
            'cta_shoppers.body' => ['nullable', 'string', 'max:320'],
            'cta_shoppers.button' => ['nullable', 'string', 'max:80'],
            'cta_shoppers.stat_listed' => ['nullable', 'string', 'max:40'],
            'cta_owners.kicker' => ['nullable', 'string', 'max:80'],
            'cta_owners.title' => ['nullable', 'string', 'max:160'],
            'cta_owners.body' => ['nullable', 'string', 'max:320'],
            'cta_owners.button' => ['nullable', 'string', 'max:80'],
            'cta_owners.checks' => ['nullable', 'array'],
            'cta_owners.checks.*' => ['nullable', 'string', 'max:160'],
            'newsletter.kicker' => ['nullable', 'string', 'max:80'],
            'newsletter.title' => ['nullable', 'string', 'max:120'],
            'newsletter.body' => ['nullable', 'string', 'max:320'],
            'newsletter.placeholder' => ['nullable', 'string', 'max:80'],
            'newsletter.button' => ['nullable', 'string', 'max:40'],
        ]);

        $defaults = OntopcouponHomepage::defaults();
        $sections = [];
        foreach (array_keys($defaults['sections']) as $key) {
            $sections[$key] = $request->boolean("sections.$key");
        }

        $payload = array_replace_recursive($defaults, $validated);
        $payload['sections'] = $sections;

        OntopcouponHomepage::save($payload);

        return redirect()
            ->route('admin.ontopcoupon-homepage.edit')
            ->with('success', 'OnTopCoupon homepage updated.');
    }
}
