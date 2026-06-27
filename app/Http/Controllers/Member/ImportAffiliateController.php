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
        CouponSpeakClient $couponSpeak,
        AffiliateImportContentBuilder $contentBuilder
    ): JsonResponse {
        $this->authorize('create', Store::class);

        $data = $request->validate([
            'affiliate_url' => ['required', 'url', 'max:500'],
            'website' => ['nullable', 'url', 'max:500'],
        ]);

        $affiliateUrl = $data['affiliate_url'];
        $storeQuery = $couponSpeak->hostFromUrl($affiliateUrl) ?? '';
        $bundle = $couponSpeak->fetchStoreBundle($storeQuery);
        $detectSource = 'local';

        if ($couponSpeak->profileIsUsable($bundle['store_profile'])) {
            $merchant = $couponSpeak->merchantFromProfile($bundle['store_profile'], $affiliateUrl);
            $detectSource = 'scan_cache';
        } else {
            $merchant = $resolver->resolve($affiliateUrl);
        }

        if (filled($data['website'] ?? null)) {
            $merchant = $resolver->enrichFromWebsite($merchant, trim($data['website']));
        }

        $merchant['category_id'] = $this->resolveCategoryId($merchant);
        $storeQuery = $merchant['domain'] ?? $storeQuery;
        $suggestedOffers = $bundle['offers'];

        $offers = $this->normalizeOffers(
            collect($suggestedOffers)->map(fn (array $offer) => [
                'code' => $offer['code'] ?? null,
                'title' => $offer['title'] ?? 'Featured offer',
                'description' => $offer['description'] ?? null,
                'expires_at' => $offer['expires_at'] ?? null,
            ])->whenEmpty(fn ($collection) => $collection->push([
                'code' => null,
                'title' => ($merchant['name'] ?? 'Store') . ' promo',
                'description' => $merchant['meta_description'] ?? null,
            ]))->all()
        );

        $previewStore = new Store([
            'name' => $merchant['name'] ?? 'New Store',
            'slug' => StoreSlug::make($merchant['name'] ?? 'new-store'),
            'category_id' => $merchant['category_id'] ?? null,
        ]);

        if (filled($merchant['category_id'] ?? null)) {
            $previewStore->setRelation('category', Category::find($merchant['category_id']));
        }

        $cachedBlog = is_array($bundle['store_profile']['generated_blog'] ?? null)
            ? $bundle['store_profile']['generated_blog']
            : null;

        if (is_array($cachedBlog) && filled($cachedBlog['content'] ?? null)) {
            $generatedBlog = $contentBuilder->sanitizeBlogOutput($cachedBlog) + ['source' => 'scan_cache'];
        } else {
            $generatedBlog = $contentBuilder->generateBlogPreview($previewStore, $offers, $merchant);
        }

        return response()->json([
            'ok' => true,
            'merchant' => $merchant,
            'store_query' => $storeQuery,
            'detect_source' => $detectSource,
            'suggested_offers' => $suggestedOffers,
            'generated_blog' => $generatedBlog,
        ]);
    }

    public function store(
        Request $request,
        AffiliateLinkResolver $resolver,
        AffiliateImportContentBuilder $contentBuilder,
        CouponSpeakClient $couponSpeak,
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
            'offers.*.expires_at' => ['nullable', 'date'],
            'generated_blog' => ['nullable', 'string', 'max:100000'],
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
        $preGeneratedBlog = $this->parseGeneratedBlog($data['generated_blog'] ?? null);
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
            $logoUrl,
            $preGeneratedBlog
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
                    'expires_at' => $offer['expires_at'],
                    'is_active' => $publish,
                ]);
            }

            $blog = $contentBuilder->blogPost($store->load('category'), $offers, $merchant, $preGeneratedBlog);
            $post = Post::create([
                'user_id' => auth()->id(),
                'store_id' => $store->id,
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

        $syncResult = $couponSpeak->syncImportedStore(
            $result['store'],
            $result['createdCoupons'],
            $data['affiliate_url'],
            [
                'website' => filled($data['website'] ?? null) ? trim($data['website']) : $result['store']->website,
                'logo' => $logoUrl,
                'meta_description' => $merchant['meta_description'] ?? null,
                'category_name' => optional(Category::find($data['category_id'] ?? null))?->name
                    ?? ($merchant['category_name'] ?? null),
                'page_title' => $merchant['page_title'] ?? null,
                'final_url' => $merchant['final_url'] ?? null,
                'faqs' => $merchant['faqs'] ?? [],
                'products' => $merchant['products'] ?? [],
                'generated_blog' => $preGeneratedBlog,
            ],
        );

        $status = $publish ? 'published' : 'saved as drafts';
        $couponCount = count($result['createdCoupons']);
        $isAdmin = auth()->user()->isAdmin();
        $successMessage = "Import complete ({$status}): {$storeName} — {$couponCount} offer(s) and 1 blog post created.";

        if ($syncResult !== null) {
            $syncedCount = (int) data_get($syncResult, 'stats.active_coupons', $couponCount);
            $successMessage .= " Synced {$syncedCount} offer(s) to Coupons Peak.";
        }

        return redirect()
            ->route('member.import-affiliate.create')
            ->with('success', $successMessage)
            ->with('import_links', [
                'store_public' => route('stores.show', $result['store']->slug),
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
     * @param  array<string, mixed>  $merchant
     */
    private function resolveCategoryId(array $merchant): ?int
    {
        if (filled($merchant['category_id'] ?? null)) {
            return (int) $merchant['category_id'];
        }

        $name = trim((string) ($merchant['category_name'] ?? ''));

        if ($name === '') {
            return null;
        }

        return Category::query()->where('name', $name)->value('id');
    }

    /**
     * @param  array<int, array{code?: ?string, title: string, description?: ?string, expires_at?: ?string}>  $offers
     * @return array<int, array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
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
                    'expires_at' => filled($offer['expires_at'] ?? null) ? $offer['expires_at'] : null,
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

    /**
     * @return array{title: string, excerpt: string, meta_title: string, meta_description: string, content: string}|null
     */
    private function parseGeneratedBlog(?string $json): ?array
    {
        if (! filled($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return null;
        }

        foreach (['title', 'excerpt', 'content'] as $required) {
            if (! filled($decoded[$required] ?? null)) {
                return null;
            }
        }

        return [
            'title' => trim((string) $decoded['title']),
            'excerpt' => trim((string) $decoded['excerpt']),
            'meta_title' => trim((string) ($decoded['meta_title'] ?? $decoded['title'])),
            'meta_description' => trim((string) ($decoded['meta_description'] ?? $decoded['excerpt'])),
            'content' => trim((string) $decoded['content']),
        ];
    }
}
