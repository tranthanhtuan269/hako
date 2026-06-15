<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Support\AffiliateImportContentBuilder;
use App\Support\AffiliateLinkResolver;
use App\Support\CouponSpeakClient;
use App\Support\HtmlCleaner;
use App\Support\PublicImage;
use App\Support\StoreSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ImportAffiliateController extends Controller
{
    public function create(AffiliateLinkResolver $resolver): View
    {
        $this->authorize('create', Store::class);

        return view('member.import-affiliate.form', [
            'categories' => $resolver->categories(),
        ]);
    }

    public function preview(
        Request $request,
        AffiliateLinkResolver $resolver,
        CouponSpeakClient $couponSpeak
    ): JsonResponse {
        $this->authorize('create', Store::class);

        $data = $request->validate([
            'affiliate_url' => ['required', 'url', 'max:500'],
        ]);

        $merchant = $resolver->resolve($data['affiliate_url']);

        $storeQuery = $merchant['domain'] ?? $couponSpeak->hostFromUrl($data['affiliate_url']);
        $suggestedOffers = $storeQuery
            ? $couponSpeak->fetchOffersByStore($storeQuery)
            : [];

        return response()->json([
            'ok' => true,
            'merchant' => $merchant,
            'store_query' => $storeQuery,
            'suggested_offers' => $suggestedOffers,
        ]);
    }

    public function store(
        Request $request,
        AffiliateLinkResolver $resolver,
        AffiliateImportContentBuilder $contentBuilder
    ): RedirectResponse {
        $this->authorize('create', Store::class);
        $this->authorize('create', Coupon::class);
        $this->authorize('create', Post::class);

        $data = $request->validate([
            'affiliate_url' => ['required', 'url', 'max:500'],
            'store_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:500'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'offers' => ['required', 'array', 'min:1'],
            'offers.*.code' => ['nullable', 'string', 'max:100'],
            'offers.*.title' => ['required', 'string', 'max:255'],
            'offers.*.description' => ['nullable', 'string'],
            'publish' => ['boolean'],
        ]);

        $merchant = $resolver->resolve($data['affiliate_url']);

        if (filled($data['website'] ?? null)) {
            $merchant = $resolver->enrichFromWebsite($merchant, trim($data['website']));
        }

        $publish = $request->boolean('publish');
        $storeName = trim($data['store_name']);
        $logoUrl = filled($data['logo_url'] ?? null) ? trim($data['logo_url']) : ($merchant['logo'] ?? null);
        $offers = $this->normalizeOffers($data['offers']);
        $userId = auth()->id();
        $domain = $merchant['domain'] ?? null;
        if (! $domain && filled($data['website'] ?? null)) {
            $domain = parse_url(trim($data['website']), PHP_URL_HOST);
            $domain = $domain ? preg_replace('/^www\./', '', $domain) : null;
        }
        $storedLogo = PublicImage::ingestStoreLogo($logoUrl, $domain, $userId);
        $storedFeatured = PublicImage::storeBlogFeaturedFromRemote($logoUrl, $userId) ?? $storedLogo;

        $result = DB::transaction(function () use (
            $data,
            $merchant,
            $publish,
            $storeName,
            $storedLogo,
            $storedFeatured,
            $offers,
            $contentBuilder,
            $logoUrl
        ) {
            $store = Store::create([
                'user_id' => auth()->id(),
                'name' => $storeName,
                'slug' => StoreSlug::make($storeName),
                'logo' => $storedLogo,
                'website' => filled($data['website'] ?? null) ? trim($data['website']) : null,
                'affiliate_url' => $data['affiliate_url'],
                'description' => HtmlCleaner::clean(
                    $contentBuilder->storeDescription(
                        $storeName,
                        $merchant['meta_description'],
                        optional(Category::find($data['category_id']))?->name
                    )
                ),
                'category_id' => filled($data['category_id'] ?? null) ? $data['category_id'] : null,
                'is_active' => $publish,
            ]);

            if (! $storedLogo) {
                $store->ensureLogoStored($logoUrl);
            }

            $createdCoupons = [];

            foreach ($offers as $offer) {
                $createdCoupons[] = Coupon::create([
                    'user_id' => auth()->id(),
                    'store_id' => $store->id,
                    'title' => $offer['title'],
                    'slug' => $this->uniqueCouponSlug($offer['title']),
                    'description' => HtmlCleaner::clean($offer['description']),
                    'code' => $offer['code'],
                    'type' => $offer['type'],
                    'is_active' => $publish,
                ]);
            }

            $blog = $contentBuilder->blogPost($store->load('category'), $offers, $merchant);
            $post = Post::create([
                'user_id' => auth()->id(),
                'title' => $blog['title'],
                'slug' => $this->uniquePostSlug($blog['title']),
                'excerpt' => $blog['excerpt'],
                'content' => $blog['content'],
                'meta_title' => $blog['meta_title'],
                'meta_description' => $blog['meta_description'],
                'featured_image' => $storedFeatured,
                'author_name' => auth()->user()->name,
                'published_at' => $publish ? now() : null,
                'is_published' => $publish,
            ]);

            return compact('store', 'createdCoupons', 'post');
        });

        $status = $publish ? 'published' : 'saved as drafts';
        $couponCount = count($result['createdCoupons']);
        $isAdmin = auth()->user()->isAdmin();

        return redirect()
            ->route('member.import-affiliate.create')
            ->with('success', "Import complete ({$status}): {$storeName} — {$couponCount} offer(s) and 1 blog post created.")
            ->with('import_links', [
                'store' => $isAdmin
                    ? route('admin.stores.edit', $result['store'])
                    : route('member.stores.edit', $result['store']),
                'post' => $isAdmin
                    ? route('admin.posts.edit', $result['post'])
                    : route('member.posts.edit', $result['post']),
                'coupons' => $isAdmin
                    ? route('admin.coupons.index')
                    : route('member.coupons.index'),
            ]);
    }

    /**
     * @param  array<int, array{code?: ?string, title: string, description?: ?string}>  $offers
     * @return array<int, array{code: ?string, title: string, description: ?string, type: string}>
     */
    private function normalizeOffers(array $offers): array
    {
        return collect($offers)
            ->map(function (array $offer) {
                $code = filled($offer['code'] ?? null) ? trim((string) $offer['code']) : null;

                return [
                    'code' => $code,
                    'title' => trim($offer['title']),
                    'description' => filled($offer['description'] ?? null) ? trim((string) $offer['description']) : null,
                    'type' => filled($code) ? 'coupon' : 'discount',
                ];
            })
            ->values()
            ->all();
    }

    private function uniqueCouponSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (Coupon::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }

    private function uniquePostSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (Post::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}
