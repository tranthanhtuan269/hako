<?php

namespace App\Support;

use App\Models\AffiliateExcelImportItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AffiliateExcelImportProcessor
{
    private const WORKSPACE_TTL_SECONDS = 1800;

    public function __construct(
        private readonly AffiliateLinkResolver $resolver = new AffiliateLinkResolver(),
        private readonly AffiliateImportContentBuilder $contentBuilder = new AffiliateImportContentBuilder(),
        private readonly CouponSpeakClient $couponSpeak = new CouponSpeakClient(),
        private readonly MerchantProductExtractor $productExtractor = new MerchantProductExtractor(),
    ) {}

    /**
     * Start a stepped processing session for one item.
     *
     * @param  array{include_detected_coupons?: bool}  $options
     * @return array{
     *     ok: bool,
     *     message: string,
     *     item_id: int,
     *     store_name: string,
     *     website: ?string,
     *     affiliate_url: string,
     *     offer_count: int,
     *     steps: list<array{key: string, label: string, status: string, detail: ?string}>,
     *     next_step: ?string,
     *     include_detected_coupons: bool
     * }
     */
    public function begin(AffiliateExcelImportItem $item, array $options = []): array
    {
        $item->refresh();
        $includeDetectedCoupons = (bool) ($options['include_detected_coupons'] ?? false);

        if ($item->status === AffiliateExcelImportItem::STATUS_FAILED) {
            $item->forceFill([
                'status' => AffiliateExcelImportItem::STATUS_PENDING,
                'error_message' => null,
                'processed_at' => null,
            ])->save();
        }

        if ($item->status !== AffiliateExcelImportItem::STATUS_PENDING
            && $item->status !== AffiliateExcelImportItem::STATUS_PROCESSING) {
            return [
                'ok' => false,
                'message' => 'Item is not pending.',
                'item_id' => $item->id,
                'store_name' => $item->store_name,
                'website' => $item->website,
                'affiliate_url' => $item->affiliate_url,
                'offer_count' => count($item->offers ?? []),
                'steps' => [],
                'next_step' => null,
                'include_detected_coupons' => $includeDetectedCoupons,
            ];
        }

        $affiliateUrl = trim((string) $item->affiliate_url);
        $website = filled($item->website) ? trim((string) $item->website) : null;
        $storeName = HtmlCleaner::normalizePlainText((string) $item->store_name);
        $offers = $this->normalizeOffers(is_array($item->offers) ? $item->offers : []);

        if ($affiliateUrl === '' || ! filter_var($affiliateUrl, FILTER_VALIDATE_URL)) {
            $item->markFailed('Missing or invalid affiliate URL.');

            return [
                'ok' => false,
                'message' => 'Missing or invalid affiliate URL.',
                'item_id' => $item->id,
                'store_name' => $storeName,
                'website' => $website,
                'affiliate_url' => $affiliateUrl,
                'offer_count' => 0,
                'steps' => [],
                'next_step' => null,
                'include_detected_coupons' => $includeDetectedCoupons,
            ];
        }

        if ($storeName === '') {
            $item->markFailed('Missing store name.');

            return [
                'ok' => false,
                'message' => 'Missing store name.',
                'item_id' => $item->id,
                'store_name' => $storeName,
                'website' => $website,
                'affiliate_url' => $affiliateUrl,
                'offer_count' => 0,
                'steps' => [],
                'next_step' => null,
                'include_detected_coupons' => $includeDetectedCoupons,
            ];
        }

        if ($offers === []) {
            $offers = [[
                'code' => null,
                'title' => 'Current deal',
                'description' => null,
                'type' => 'discount',
                'expires_at' => null,
            ]];
        }

        $item->markProcessing();

        $steps = $this->defaultNewStoreSteps();
        $workspace = [
            'user_id' => (int) $item->user_id,
            'store_name' => $storeName,
            'affiliate_url' => $affiliateUrl,
            'website' => $website,
            'excel_logo' => filled($item->logo) ? trim((string) $item->logo) : null,
            'offers' => $offers,
            'excel_offers' => $offers,
            'include_detected_coupons' => $includeDetectedCoupons,
            'category_name' => $item->category_name,
            'was_existing' => false,
            'existing_store_id' => null,
            'merchant' => [],
            'domain' => null,
            'category_id' => null,
            'stored_logo' => null,
            'stored_featured' => null,
            'logo_url' => null,
            'store_id' => null,
            'slug' => null,
            'created_coupon_ids' => [],
            'coupons_added' => 0,
            'steps' => $steps,
        ];

        $this->putWorkspace($item->id, $workspace);

        $modeLabel = $includeDetectedCoupons
            ? 'Excel + detected/Scan coupons'
            : 'Excel coupons only';

        return [
            'ok' => true,
            'message' => 'Ready to process '.$storeName.' ('.$modeLabel.')',
            'item_id' => $item->id,
            'store_name' => $storeName,
            'website' => $website,
            'affiliate_url' => $affiliateUrl,
            'offer_count' => count($offers),
            'steps' => $steps,
            'next_step' => $steps[0]['key'] ?? null,
            'include_detected_coupons' => $includeDetectedCoupons,
        ];
    }

    /**
     * Run a single step for an active processing session.
     *
     * @return array{
     *     ok: bool,
     *     message: string,
     *     detail: ?string,
     *     item_id: int,
     *     store_name: string,
     *     step: string,
     *     steps: list<array{key: string, label: string, status: string, detail: ?string}>,
     *     next_step: ?string,
     *     item_done: bool,
     *     was_existing_store: bool,
     *     store_id: ?int,
     *     coupons_added: int
     * }
     */
    public function runStep(AffiliateExcelImportItem $item, string $step): array
    {
        $workspace = $this->getWorkspace($item->id);

        if ($workspace === null) {
            $begin = $this->begin($item);

            if (! $begin['ok']) {
                return [
                    'ok' => false,
                    'message' => $begin['message'],
                    'detail' => $begin['message'],
                    'item_id' => $item->id,
                    'store_name' => $item->store_name,
                    'step' => $step,
                    'steps' => [],
                    'next_step' => null,
                    'item_done' => true,
                    'was_existing_store' => false,
                    'store_id' => null,
                    'coupons_added' => 0,
                ];
            }

            $workspace = $this->getWorkspace($item->id);
        }

        if ($workspace === null) {
            return [
                'ok' => false,
                'message' => 'Processing session expired. Retry this store.',
                'detail' => null,
                'item_id' => $item->id,
                'store_name' => $item->store_name,
                'step' => $step,
                'steps' => [],
                'next_step' => null,
                'item_done' => true,
                'was_existing_store' => false,
                'store_id' => null,
                'coupons_added' => 0,
            ];
        }

        $this->setStepStatus($workspace, $step, 'running');
        $this->putWorkspace($item->id, $workspace);

        try {
            $detail = match ($step) {
                'check_store' => $this->stepCheckStore($workspace),
                'find_logo' => $this->stepFindLogo($workspace),
                'create_store' => $this->stepCreateStore($workspace),
                'write_content' => $this->stepWriteContent($workspace),
                'create_coupons' => $this->stepCreateCoupons($workspace),
                'finish' => $this->stepFinish($item, $workspace),
                default => throw new \RuntimeException('Unknown step: '.$step),
            };

            $this->setStepStatus($workspace, $step, 'done', $detail);
            $next = $this->nextPendingStep($workspace);
            $itemDone = $next === null;

            if ($itemDone && $step !== 'finish') {
                // Safety: always end with finish if somehow skipped.
                $next = 'finish';
                $itemDone = false;
            }

            if ($itemDone) {
                $store = Store::query()->find($workspace['store_id'] ?? null);
                if ($store) {
                    $item->markDone($store, (int) ($workspace['coupons_added'] ?? 0), (bool) ($workspace['was_existing'] ?? false));
                    $item->import?->refreshCounters();
                }
                $this->forgetWorkspace($item->id);
            } else {
                $this->putWorkspace($item->id, $workspace);
            }

            return [
                'ok' => true,
                'message' => $detail,
                'detail' => $detail,
                'item_id' => $item->id,
                'store_name' => (string) $workspace['store_name'],
                'step' => $step,
                'steps' => $workspace['steps'],
                'next_step' => $next,
                'item_done' => $itemDone,
                'was_existing_store' => (bool) ($workspace['was_existing'] ?? false),
                'store_id' => $workspace['store_id'] ?? null,
                'coupons_added' => (int) ($workspace['coupons_added'] ?? 0),
            ];
        } catch (\Throwable $e) {
            report($e);
            $this->setStepStatus($workspace, $step, 'failed', $e->getMessage());
            $this->putWorkspace($item->id, $workspace);
            $item->markFailed(Str::limit($e->getMessage(), 500, ''));
            $item->import?->refreshCounters();
            $this->forgetWorkspace($item->id);

            return [
                'ok' => false,
                'message' => $e->getMessage(),
                'detail' => $e->getMessage(),
                'item_id' => $item->id,
                'store_name' => (string) ($workspace['store_name'] ?? $item->store_name),
                'step' => $step,
                'steps' => $workspace['steps'],
                'next_step' => null,
                'item_done' => true,
                'was_existing_store' => (bool) ($workspace['was_existing'] ?? false),
                'store_id' => $workspace['store_id'] ?? null,
                'coupons_added' => (int) ($workspace['coupons_added'] ?? 0),
            ];
        }
    }

    /**
     * @return array{
     *     ok: bool,
     *     message: string,
     *     store_id: ?int,
     *     coupons_added: int,
     *     was_existing_store: bool
     * }
     */
    public function processItem(AffiliateExcelImportItem $item): array
    {
        $begin = $this->begin($item);

        if (! $begin['ok']) {
            return [
                'ok' => false,
                'message' => $begin['message'],
                'store_id' => null,
                'coupons_added' => 0,
                'was_existing_store' => false,
            ];
        }

        $next = $begin['next_step'];
        $last = null;

        while ($next) {
            $last = $this->runStep($item->fresh(), $next);
            if (! $last['ok']) {
                return [
                    'ok' => false,
                    'message' => $last['message'],
                    'store_id' => $last['store_id'],
                    'coupons_added' => $last['coupons_added'],
                    'was_existing_store' => $last['was_existing_store'],
                ];
            }
            $next = $last['next_step'];
        }

        return [
            'ok' => true,
            'message' => $last['message'] ?? 'Done',
            'store_id' => $last['store_id'] ?? null,
            'coupons_added' => $last['coupons_added'] ?? 0,
            'was_existing_store' => $last['was_existing_store'] ?? false,
        ];
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepCheckStore(array &$workspace): string
    {
        $userId = (int) $workspace['user_id'];
        $website = $workspace['website'] ?? null;
        $affiliateUrl = (string) $workspace['affiliate_url'];

        $domain = $this->guessDomain($website, $affiliateUrl);
        $scanBundle = $this->fetchScanBundle($affiliateUrl, $website, $domain);
        $fromScan = $this->couponSpeak->profileIsUsable($scanBundle['store_profile'] ?? null)
            || filled($scanBundle['scan_logo'] ?? null)
            || ((is_array($scanBundle['offers'] ?? null) ? $scanBundle['offers'] : []) !== []);

        $workspace['domain'] = $domain;
        $workspace['scan_bundle'] = $scanBundle;
        $workspace['from_scan'] = $fromScan;

        $existing = Store::findForMerchantImport($userId, $website, $affiliateUrl, $domain);

        if ($existing) {
            $workspace['was_existing'] = true;
            $workspace['existing_store_id'] = $existing->id;
            $workspace['store_id'] = $existing->id;
            $workspace['steps'] = $this->existingStoreSteps($workspace['steps']);

            // Optionally reuse Scan coupons when appending to an existing local store.
            if ($fromScan && $this->wantsDetectedCoupons($workspace)) {
                $scanOffers = $this->normalizeScanOffers($scanBundle['offers'] ?? []);
                $workspace['offers'] = $this->mergeOffers(
                    is_array($workspace['excel_offers'] ?? $workspace['offers'] ?? null)
                        ? ($workspace['excel_offers'] ?? $workspace['offers'])
                        : [],
                    $scanOffers
                );

                return 'Store already exists locally: '.$existing->name
                    .' — will append Excel + '.count($scanOffers).' Scan coupon(s).';
            }

            return 'Store already exists: '.$existing->name
                .' — will append Excel coupons only.';
        }

        $workspace['was_existing'] = false;

        if ($fromScan) {
            $scanName = trim((string) ($scanBundle['store_profile']['name'] ?? ''));
            $couponMode = $this->wantsDetectedCoupons($workspace)
                ? 'logo + Scan coupons'
                : 'logo (Excel coupons only)';

            return 'Not on this site yet — found on Scan'
                .($scanName !== '' ? ' (“'.$scanName.'”)' : '')
                .' — will reuse '.$couponMode.'.';
        }

        return 'Not found locally or on Scan — will detect affiliate link.';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepFindLogo(array &$workspace): string
    {
        if (! empty($workspace['was_existing'])) {
            $this->setStepStatus($workspace, 'find_logo', 'skipped', 'Skipped for existing store');

            return 'Skipped (existing store).';
        }

        $affiliateUrl = (string) $workspace['affiliate_url'];
        $website = $workspace['website'] ?? null;
        $userId = (int) $workspace['user_id'];
        $excelLogo = filled($workspace['excel_logo'] ?? null) ? (string) $workspace['excel_logo'] : null;
        $scanBundle = is_array($workspace['scan_bundle'] ?? null) ? $workspace['scan_bundle'] : [];
        $profileUsable = $this->couponSpeak->profileIsUsable($scanBundle['store_profile'] ?? null);
        $scanLogo = filled($scanBundle['scan_logo'] ?? null) ? (string) $scanBundle['scan_logo'] : null;
        $scanOffers = $this->wantsDetectedCoupons($workspace)
            ? $this->normalizeScanOffers($scanBundle['offers'] ?? [])
            : [];

        if ($profileUsable) {
            $merchant = $this->couponSpeak->merchantFromProfile(
                $scanBundle['store_profile'],
                $affiliateUrl
            );
            $merchant['product_focus'] = false;
            $merchant['affiliate_url'] = $affiliateUrl;

            if ($scanLogo) {
                $merchant['logo'] = $scanLogo;
            }

            if (! $website && filled($merchant['final_url'] ?? null)) {
                $website = (string) $merchant['final_url'];
                $workspace['website'] = $website;
            }

            if (filled($merchant['name'] ?? null) && $this->shouldPreferScanName((string) $workspace['store_name'], (string) $merchant['name'])) {
                $workspace['store_name'] = HtmlCleaner::normalizePlainText((string) $merchant['name']);
            }

            if ($scanOffers !== []) {
                $workspace['offers'] = $this->mergeOffers(
                    is_array($workspace['excel_offers'] ?? $workspace['offers'] ?? null)
                        ? ($workspace['excel_offers'] ?? $workspace['offers'])
                        : [],
                    $scanOffers
                );
            }

            $sourceNote = 'Scan cache';
        } elseif ($scanLogo || $scanOffers !== []) {
            // Partial Scan hit: reuse logo (and coupons if enabled), avoid full detect when logo exists.
            if ($scanOffers !== []) {
                $workspace['offers'] = $this->mergeOffers(
                    is_array($workspace['excel_offers'] ?? $workspace['offers'] ?? null)
                        ? ($workspace['excel_offers'] ?? $workspace['offers'])
                        : [],
                    $scanOffers
                );
            }

            if ($scanLogo) {
                $merchant = [
                    'affiliate_url' => $affiliateUrl,
                    'final_url' => $website ?: $affiliateUrl,
                    'domain' => $workspace['domain'] ?? $this->couponSpeak->hostFromUrl($affiliateUrl),
                    'name' => $workspace['store_name'],
                    'logo' => $scanLogo,
                    'product_focus' => false,
                    'products' => [],
                    'faqs' => [],
                ];
                $sourceNote = $scanOffers !== [] ? 'Scan logo/coupons' : 'Scan logo';
            } else {
                $merchant = $this->resolveMerchantLocally($affiliateUrl, $website);
                $sourceNote = 'Local detect + Scan coupons';
            }
        } else {
            $merchant = $this->resolveMerchantLocally($affiliateUrl, $website);
            $sourceNote = 'Local detect';
        }

        $domain = $merchant['domain'] ?? ($workspace['domain'] ?? null);
        if (! $domain && $website) {
            $host = parse_url($website, PHP_URL_HOST);
            $domain = $host ? preg_replace('/^www\./', '', $host) : null;
        }

        // Excel Logo column wins when provided; otherwise keep Scan/detect behavior.
        $logoUrl = $merchant['logo'] ?? $scanLogo;
        $storedLogo = null;

        if ($excelLogo) {
            $excelStored = PublicImage::ingestRemote($excelLogo, "stores/{$userId}/logos");

            if ($excelStored) {
                $storedLogo = PublicImage::resizeRasterToWidth($excelStored, PublicImage::STORE_LOGO_WIDTH) ?? $excelStored;
                $logoUrl = $excelLogo;
                $sourceNote = 'Excel logo';
            } else {
                $storedLogo = PublicImage::ingestStoreLogo(
                    is_string($logoUrl) ? $logoUrl : null,
                    $domain,
                    $userId
                );
                $sourceNote .= ' (Excel logo download failed, used detect)';
            }
        } else {
            $storedLogo = PublicImage::ingestStoreLogo(
                is_string($logoUrl) ? $logoUrl : null,
                $domain,
                $userId
            );
        }

        if ($storedLogo && ($localLogoUrl = PublicImage::url($storedLogo))) {
            $merchant['logo'] = $localLogoUrl;
        }

        $featuredCandidate = filled($merchant['banner'] ?? null)
            ? (string) $merchant['banner']
            : (filled($merchant['products'][0]['image'] ?? null) ? (string) $merchant['products'][0]['image'] : null);

        $storedFeatured = null;
        if ($featuredCandidate) {
            $storedFeatured = PublicImage::ingestRemote($featuredCandidate, "blogs/{$userId}/featured");
        }
        if (! $storedFeatured) {
            $storedFeatured = PublicImage::isScanAssetUrl(is_string($logoUrl) ? $logoUrl : null)
                ? $storedLogo
                : (PublicImage::storeBlogFeaturedFromRemote(is_string($logoUrl) ? $logoUrl : null, $userId) ?? $storedLogo);
        }

        $workspace['merchant'] = $merchant;
        $workspace['domain'] = $domain;
        $workspace['category_id'] = $this->resolveCategoryId($workspace['category_name'] ?? null, $merchant);
        $workspace['stored_logo'] = $storedLogo;
        $workspace['stored_featured'] = $storedFeatured;
        $workspace['logo_url'] = is_string($logoUrl) ? $logoUrl : null;
        $workspace['from_scan'] = $profileUsable || $scanLogo !== null || $scanOffers !== [];

        $offerCount = count($workspace['offers'] ?? []);
        $couponNote = $this->wantsDetectedCoupons($workspace)
            ? $offerCount.' offer(s) (Excel + detected)'
            : $offerCount.' Excel offer(s)';

        if ($storedLogo) {
            return $sourceNote.': logo saved, '.$couponNote.'.';
        }

        return $sourceNote.': no logo yet, '.$couponNote.'.';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepCreateStore(array &$workspace): string
    {
        if (! empty($workspace['was_existing'])) {
            $this->setStepStatus($workspace, 'create_store', 'skipped', 'Using existing store');

            return 'Using existing store.';
        }

        $userId = (int) $workspace['user_id'];
        $storeName = (string) $workspace['store_name'];
        $slug = StoreSlug::make($storeName);
        $merchant = is_array($workspace['merchant'] ?? null) ? $workspace['merchant'] : [];
        $offers = is_array($workspace['offers'] ?? null) ? $workspace['offers'] : [];
        $categoryId = $workspace['category_id'] ?? null;
        $categoryName = optional(Category::find($categoryId))?->name ?? ($merchant['category_name'] ?? null);
        $storedLogo = $workspace['stored_logo'] ?? null;
        $website = $workspace['website'] ?? null;
        $affiliateUrl = (string) $workspace['affiliate_url'];
        $logoUrl = $workspace['logo_url'] ?? null;

        $store = Store::create([
            'user_id' => $userId,
            'slug' => $slug,
            'name' => $storeName,
            'logo' => $storedLogo,
            'website' => $website,
            'affiliate_url' => $affiliateUrl,
            'description' => null,
            'category_id' => $categoryId,
            'is_active' => true,
        ]);

        if (! $storedLogo) {
            $store->ensureLogoStored(is_string($logoUrl) ? $logoUrl : null);
            $workspace['stored_logo'] = $store->fresh()->logo;
        }

        $workspace['store_id'] = $store->id;
        $workspace['slug'] = $slug;
        $workspace['category_name_resolved'] = $categoryName;

        return 'Created store “'.$store->name.'”.';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepWriteContent(array &$workspace): string
    {
        if (! empty($workspace['was_existing'])) {
            $this->setStepStatus($workspace, 'write_content', 'skipped', 'Skipped for existing store');

            return 'Skipped writing article (existing store).';
        }

        $store = Store::query()->find($workspace['store_id'] ?? null);
        if (! $store) {
            throw new \RuntimeException('Store missing before writing content.');
        }

        $userId = (int) $workspace['user_id'];
        $merchant = is_array($workspace['merchant'] ?? null) ? $workspace['merchant'] : [];
        $offers = is_array($workspace['offers'] ?? null) ? $workspace['offers'] : [];
        $affiliateUrl = (string) $workspace['affiliate_url'];
        $categoryName = $workspace['category_name_resolved']
            ?? optional($store->category)->name
            ?? ($merchant['category_name'] ?? null);
        $storedFeatured = $workspace['stored_featured'] ?? null;

        $description = HtmlCleaner::clean(
            PublicImage::localizeHtmlImages(
                $this->contentBuilder->storeDescription(
                    $store->name,
                    $store->slug,
                    $affiliateUrl,
                    $categoryName,
                    $offers,
                    $merchant,
                ),
                "stores/{$userId}/content"
            )
        );
        $store->update(['description' => $description]);

        $blog = $this->contentBuilder->blogPost($store->load('category'), $offers, $merchant);
        $blog['content'] = HtmlCleaner::clean(
            PublicImage::localizeHtmlImages($blog['content'] ?? '', "blogs/{$userId}/content")
        ) ?? ($blog['content'] ?? '');

        Post::create([
            'user_id' => $userId,
            'store_id' => $store->id,
            'title' => $blog['title'],
            'excerpt' => $blog['excerpt'],
            'content' => $blog['content'],
            'meta_title' => $blog['meta_title'],
            'meta_description' => $blog['meta_description'],
            'featured_image' => $storedFeatured,
            'author_name' => User::query()->whereKey($userId)->value('name') ?? 'Admin',
            'published_at' => now(),
            'is_published' => true,
            'slug' => Post::stableReviewPostSlug($store),
        ]);

        return 'Wrote store description and blog article.';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepCreateCoupons(array &$workspace): string
    {
        $store = Store::query()->find($workspace['store_id'] ?? $workspace['existing_store_id'] ?? null);
        if (! $store) {
            throw new \RuntimeException('Store missing before creating coupons.');
        }

        $userId = (int) $workspace['user_id'];
        $offers = is_array($workspace['offers'] ?? null) ? $workspace['offers'] : [];
        $affiliateUrl = (string) $workspace['affiliate_url'];
        $website = $workspace['website'] ?? null;

        if (! empty($workspace['was_existing'])) {
            $offers = $this->filterNewOffers($store, $userId, $offers);
            $updates = [];
            if (filled($affiliateUrl) && ! filled($store->affiliate_url)) {
                $updates['affiliate_url'] = $affiliateUrl;
            }
            if (filled($website) && ! filled($store->website)) {
                $updates['website'] = $website;
            }
            if ($updates !== []) {
                $store->update($updates);
            }
        }

        $maxSort = (int) Coupon::query()
            ->where('store_id', $store->id)
            ->where('user_id', $userId)
            ->max('store_sort_order');

        $created = DB::transaction(fn () => $this->createCoupons($store, $userId, $offers, $maxSort));

        $workspace['created_coupon_ids'] = array_map(fn (Coupon $c) => $c->id, $created);
        $workspace['coupons_added'] = count($created);
        $workspace['store_id'] = $store->id;

        if ($created === []) {
            return 'No new coupons to add (all already exist).';
        }

        return 'Created '.count($created).' coupon(s).';
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function stepFinish(AffiliateExcelImportItem $item, array &$workspace): string
    {
        $store = Store::query()->with('category')->find($workspace['store_id'] ?? null);
        if (! $store) {
            throw new \RuntimeException('Store missing at finish step.');
        }

        $couponIds = is_array($workspace['created_coupon_ids'] ?? null) ? $workspace['created_coupon_ids'] : [];
        $createdCoupons = $couponIds !== []
            ? Coupon::query()->whereIn('id', $couponIds)->get()->all()
            : [];

        $merchant = is_array($workspace['merchant'] ?? null) ? $workspace['merchant'] : [];
        $affiliateUrl = (string) $workspace['affiliate_url'];
        $website = $workspace['website'] ?? $store->website;

        if ($createdCoupons !== [] || empty($workspace['was_existing'])) {
            $this->couponSpeak->syncImportedStore(
                $store,
                $createdCoupons,
                $affiliateUrl,
                [
                    'website' => $website,
                    'logo' => $workspace['logo_url'] ?? null,
                    'meta_description' => $merchant['meta_description'] ?? null,
                    'category_name' => $store->category?->name ?? ($merchant['category_name'] ?? null),
                    'page_title' => $merchant['page_title'] ?? null,
                    'final_url' => $merchant['final_url'] ?? null,
                    'faqs' => $merchant['faqs'] ?? [],
                    'products' => $merchant['products'] ?? [],
                ],
            );
        }

        $mode = ! empty($workspace['was_existing']) ? 'Existing store updated' : 'New store created';

        return $mode.' — '.(int) ($workspace['coupons_added'] ?? 0).' coupon(s).';
    }

    /**
     * @return list<array{key: string, label: string, status: string, detail: ?string}>
     */
    private function defaultNewStoreSteps(): array
    {
        return [
            ['key' => 'check_store', 'label' => 'Check Store (local + Scan)', 'status' => 'pending', 'detail' => null],
            ['key' => 'find_logo', 'label' => 'Tìm logo / data (Scan ưu tiên)', 'status' => 'pending', 'detail' => null],
            ['key' => 'create_store', 'label' => 'Tạo store', 'status' => 'pending', 'detail' => null],
            ['key' => 'write_content', 'label' => 'Viết bài', 'status' => 'pending', 'detail' => null],
            ['key' => 'create_coupons', 'label' => 'Tạo coupon', 'status' => 'pending', 'detail' => null],
            ['key' => 'finish', 'label' => 'Hoàn tất', 'status' => 'pending', 'detail' => null],
        ];
    }

    /**
     * After discovering an existing store, skip create/content/logo steps.
     *
     * @param  list<array{key: string, label: string, status: string, detail: ?string}>  $current
     * @return list<array{key: string, label: string, status: string, detail: ?string}>
     */
    private function existingStoreSteps(array $current): array
    {
        $keep = ['check_store', 'create_coupons', 'finish'];
        $out = [];

        foreach ($current as $step) {
            if (in_array($step['key'], $keep, true)) {
                $out[] = $step;
                continue;
            }

            $step['status'] = 'skipped';
            $step['detail'] = 'Skipped — store already exists';
            $out[] = $step;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function setStepStatus(array &$workspace, string $key, string $status, ?string $detail = null): void
    {
        foreach ($workspace['steps'] as &$step) {
            if (($step['key'] ?? '') === $key) {
                $step['status'] = $status;
                if ($detail !== null) {
                    $step['detail'] = $detail;
                }
            }
        }
        unset($step);
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function nextPendingStep(array $workspace): ?string
    {
        foreach ($workspace['steps'] as $step) {
            if (($step['status'] ?? '') === 'pending') {
                return $step['key'];
            }
        }

        return null;
    }

    private function workspaceKey(int $itemId): string
    {
        return 'affiliate_excel_item_workspace_'.$itemId;
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function putWorkspace(int $itemId, array $workspace): void
    {
        Cache::put($this->workspaceKey($itemId), $workspace, self::WORKSPACE_TTL_SECONDS);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getWorkspace(int $itemId): ?array
    {
        $data = Cache::get($this->workspaceKey($itemId));

        return is_array($data) ? $data : null;
    }

    private function forgetWorkspace(int $itemId): void
    {
        Cache::forget($this->workspaceKey($itemId));
    }

    private function wantsDetectedCoupons(array $workspace): bool
    {
        return ! empty($workspace['include_detected_coupons']);
    }

    /**
     * @return array{
     *     offers: list<array<string, mixed>>,
     *     store_profile: ?array<string, mixed>,
     *     scan_logo: ?string,
     *     profile_cached: bool
     * }
     */
    private function fetchScanBundle(string $affiliateUrl, ?string $website, ?string $domain): array
    {
        $empty = [
            'offers' => [],
            'store_profile' => null,
            'scan_logo' => null,
            'profile_cached' => false,
        ];

        $queries = array_values(array_unique(array_filter([
            $domain,
            $website ? $this->couponSpeak->hostFromUrl($website) : null,
            $this->couponSpeak->hostFromUrl($affiliateUrl),
        ])));

        foreach ($queries as $query) {
            $bundle = $this->couponSpeak->fetchStoreBundle((string) $query);
            $usable = $this->couponSpeak->profileIsUsable($bundle['store_profile'] ?? null)
                || filled($bundle['scan_logo'] ?? null)
                || ((is_array($bundle['offers'] ?? null) ? $bundle['offers'] : []) !== []);

            if ($usable) {
                return $bundle;
            }
        }

        return $empty;
    }

    private function guessDomain(?string $website, string $affiliateUrl): ?string
    {
        if ($website) {
            $host = parse_url($website, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return preg_replace('/^www\./', '', strtolower($host));
            }
        }

        return $this->couponSpeak->hostFromUrl($affiliateUrl);
    }

    /**
     * Local scrape/detect only — used when Scan has no usable store cache.
     *
     * @return array<string, mixed>
     */
    private function resolveMerchantLocally(string $affiliateUrl, ?string $website): array
    {
        $finalUrl = $this->resolver->finalUrl($affiliateUrl);
        $merchant = $this->resolver->resolve($affiliateUrl, false);

        if ($website) {
            $merchant = $this->resolver->enrichFromWebsite($merchant, $website, false);
        }

        if (empty($merchant['banner'])) {
            $bannerSource = $website
                ?: (filled($merchant['domain'] ?? null) ? 'https://'.$merchant['domain'] : $finalUrl);
            $merchant = $this->resolver->enrichFromWebsite($merchant, $bannerSource, false);
        }

        $merchant['product_focus'] = false;
        $merchant['affiliate_url'] = $affiliateUrl;
        $merchant['final_url'] = $merchant['final_url'] ?? $finalUrl;

        if (! filled($merchant['domain'] ?? null)) {
            $merchant['domain'] = $this->couponSpeak->hostFromUrl($finalUrl)
                ?? $this->couponSpeak->hostFromUrl($affiliateUrl);
        }

        $products = is_array($merchant['products'] ?? null) ? $merchant['products'] : [];
        $merchant['products'] = $this->productExtractor->uniqueTake(
            array_values(array_filter($products, fn ($product) => is_array($product))),
            max(count($products), 1)
        );

        return $merchant;
    }

    /**
     * @param  list<array{code?: ?string, title?: string, description?: ?string, type?: string, expires_at?: ?string}>  $excelOffers
     * @param  list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>  $scanOffers
     * @return list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
     */
    private function mergeOffers(array $excelOffers, array $scanOffers): array
    {
        // Scan first so cached verified offers win on duplicate keys, then Excel extras.
        return $this->uniqueOfferList(array_merge($scanOffers, $excelOffers));
    }

    /**
     * @param  list<array<string, mixed>>  $offers
     * @return list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
     */
    private function normalizeScanOffers(array $offers): array
    {
        return $this->normalizeOffers(array_map(function (array $offer) {
            return [
                'code' => $offer['code'] ?? null,
                'title' => $offer['title'] ?? ($offer['discount_label'] ?? 'Offer'),
                'description' => $offer['description'] ?? null,
                'type' => filled($offer['code'] ?? null) ? 'coupon' : 'discount',
                'expires_at' => $offer['expires_at'] ?? null,
            ];
        }, $offers));
    }

    /**
     * @param  list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>  $offers
     * @return list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
     */
    private function uniqueOfferList(array $offers): array
    {
        $out = [];
        $seen = [];

        foreach ($offers as $offer) {
            if (! is_array($offer) || ! filled($offer['title'] ?? null)) {
                continue;
            }

            $key = $this->offerKey($offer['code'] ?? null, (string) $offer['title']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'code' => $offer['code'] ?? null,
                'title' => (string) $offer['title'],
                'description' => $offer['description'] ?? null,
                'type' => $offer['type'] ?? (filled($offer['code'] ?? null) ? 'coupon' : 'discount'),
                'expires_at' => $offer['expires_at'] ?? null,
            ];
        }

        return $out;
    }

    private function shouldPreferScanName(string $excelName, string $scanName): bool
    {
        $excelName = trim($excelName);
        $scanName = trim($scanName);

        if ($scanName === '') {
            return false;
        }

        if ($excelName === '' || preg_match('/^#(NAME\?|REF!|VALUE!)/i', $excelName)) {
            return true;
        }

        // Prefer Scan when Excel looks like a slug/domain fragment.
        return ! str_contains($excelName, ' ')
            && strlen($scanName) > strlen($excelName)
            && Str::contains(Str::lower($scanName), Str::lower($excelName));
    }

    /**
     * @param  list<array{code?: ?string, title?: string, description?: ?string, type?: string}>  $offers
     * @return list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
     */
    private function normalizeOffers(array $offers): array
    {
        return collect($offers)
            ->map(function (array $offer) {
                $code = filled($offer['code'] ?? null) ? trim((string) $offer['code']) : null;
                $title = HtmlCleaner::normalizePlainText((string) ($offer['title'] ?? ''));

                if ($title === '') {
                    return null;
                }

                return [
                    'code' => $code,
                    'title' => $title,
                    'description' => filled($offer['description'] ?? null)
                        ? HtmlCleaner::normalizePlainText((string) $offer['description'])
                        : null,
                    'type' => filled($code) ? 'coupon' : (string) ($offer['type'] ?? 'discount'),
                    'expires_at' => filled($offer['expires_at'] ?? null) ? $offer['expires_at'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>  $offers
     * @return list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>
     */
    private function filterNewOffers(Store $store, int $userId, array $offers): array
    {
        $existing = Coupon::query()
            ->where('store_id', $store->id)
            ->where('user_id', $userId)
            ->get(['code', 'title']);

        $seen = [];

        foreach ($existing as $coupon) {
            $seen[$this->offerKey($coupon->code, $coupon->title)] = true;
        }

        return array_values(array_filter($offers, function (array $offer) use (&$seen) {
            $key = $this->offerKey($offer['code'] ?? null, $offer['title']);

            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;

            return true;
        }));
    }

    private function offerKey(?string $code, string $title): string
    {
        return Str::lower(trim(($code ?? '').'|'.$title));
    }

    /**
     * @param  list<array{code: ?string, title: string, description: ?string, type: string, expires_at: ?string}>  $offers
     * @return list<Coupon>
     */
    private function createCoupons(Store $store, int $userId, array $offers, int $sortBase): array
    {
        $created = [];
        $count = count($offers);

        foreach ($offers as $index => $offer) {
            $sortOrder = $sortBase + ($count - $index);

            $created[] = Coupon::create([
                'user_id' => $userId,
                'store_id' => $store->id,
                'title' => $offer['title'],
                'slug' => $this->uniqueCouponSlug($offer['title']),
                'description' => HtmlCleaner::clean($offer['description']),
                'code' => $offer['code'],
                'type' => $offer['type'],
                'expires_at' => $offer['expires_at'],
                'is_active' => true,
                'store_sort_order' => max(1, $sortOrder),
                'coupons_sort_order' => max(1, $sortOrder),
            ]);
        }

        return $created;
    }

    private function resolveCategoryId(?string $categoryName, array $merchant): ?int
    {
        $name = trim((string) ($categoryName ?: ($merchant['category_name'] ?? '')));

        if ($name === '') {
            if (filled($merchant['category_id'] ?? null)) {
                return (int) $merchant['category_id'];
            }

            return null;
        }

        return Category::query()->where('name', $name)->value('id');
    }

    private function uniqueCouponSlug(string $title): string
    {
        $slug = Str::slug($title) ?: 'offer';
        $original = $slug;
        $i = 1;

        while (Coupon::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }
}
