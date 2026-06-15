<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $static = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => route('coupons.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('stores.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('categories.index'), 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => route('blog.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('pages.about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('pages.contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('pages.privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('pages.terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('pages.cookies'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('pages.disclaimer'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('coupons.index', ['type' => 'coupon']), 'priority' => '0.85', 'changefreq' => 'daily'],
            ['loc' => route('coupons.index', ['type' => 'discount']), 'priority' => '0.85', 'changefreq' => 'daily'],
        ];

        foreach ($static as $item) {
            $urls[] = $item;
        }

        foreach (Post::published()->orderByDesc('published_at')->get() as $post) {
            $urls[] = [
                'loc' => route('blog.show', $post->slug),
                'lastmod' => $post->updated_at->toAtomString(),
                'priority' => '0.8',
                'changefreq' => 'weekly',
            ];
        }

        foreach (Coupon::valid()->get() as $coupon) {
            $urls[] = [
                'loc' => route('coupons.show', $coupon->slug),
                'lastmod' => $coupon->updated_at->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        foreach (Store::active()->get() as $store) {
            $urls[] = [
                'loc' => route('stores.show', $store->slug),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        foreach (Category::active()->get() as $category) {
            $urls[] = [
                'loc' => route('categories.show', $category->slug),
                'priority' => '0.6',
                'changefreq' => 'weekly',
            ];
        }

        return response()
            ->view('sitemap.index', compact('urls'))
            ->header('Content-Type', 'application/xml');
    }
}
