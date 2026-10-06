<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Throwable;

final class OntopcouponTheme
{
    /** Store list cards rotate through these accent sets (ink, solid, tint, line). */
    private const PALETTES = [
        ['ink' => '#3F6212', 'solid' => '#65A30D', 'tint' => '#F7FEE7', 'line' => '#D9F99D'],
        ['ink' => '#5B21B6', 'solid' => '#8B5CF6', 'tint' => '#F5F3FF', 'line' => '#DDD6FE'],
        ['ink' => '#9A3412', 'solid' => '#F97316', 'tint' => '#FFF7ED', 'line' => '#FED7AA'],
        ['ink' => '#075985', 'solid' => '#0EA5E9', 'tint' => '#F0F9FF', 'line' => '#BAE6FD'],
        ['ink' => '#065F46', 'solid' => '#10B981', 'tint' => '#ECFDF5', 'line' => '#A7F3D0'],
    ];

    /** Keyword => [lucide icon, accent colour] for category tiles. */
    private const CATEGORY_STYLES = [
        'fashion|cloth|apparel|shoe|wear|jewel' => ['shirt', '#e8505b'],
        'electronic|tech|gadget|computer|phone' => ['laptop', '#388bfd'],
        'food|drink|grocer|restaurant|meal|coffee' => ['utensils', '#f0932b'],
        'travel|hotel|flight|trip|vacation' => ['plane', '#48bbff'],
        'beauty|cosmetic|skin|makeup|health|wellness' => ['sparkles', '#c46bff'],
        'sport|fitness|outdoor|gym' => ['dumbbell', '#34d399'],
        'home|garden|furniture|kitchen|decor' => ['house', '#fbbf24'],
        'software|app|saas|digital|course|education' => ['monitor', '#64748b'],
        'game|gaming|toy' => ['gamepad', '#f43f5e'],
        'auto|car|vehicle' => ['car', '#0ea5e9'],
        'gift|flower|party' => ['gift', '#ec4899'],
        'pet' => ['heart', '#f97316'],
    ];

    private const FALLBACK_COLORS = ['#e8505b', '#388bfd', '#f0932b', '#48bbff', '#c46bff', '#34d399', '#fbbf24', '#64748b'];

    /** Blog card header glows (r,g,b). */
    private const BLOG_GLOWS = ['177,244,103', '56,189,248', '167,139,250'];

    private const ICONS = [
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'arrow-up-right' => '<path d="M7 7h10v10"/><path d="M7 17 17 7"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>',
        'percent' => '<line x1="19" x2="5" y1="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'book-open' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>',
        'zap' => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'circle-check' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'external-link' => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'store' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/>',
        'shirt' => '<path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/>',
        'laptop' => '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16"/>',
        'utensils' => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
        'plane' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
        'sparkles' => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>',
        'dumbbell' => '<path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.768a2 2 0 1 1-2.829-2.829l6.364-6.364a2 2 0 1 1 2.829 2.829l-1.768 1.767a2 2 0 1 1 2.828 2.829z"/><path d="m21.5 21.5-1.4-1.4"/><path d="M3.9 3.9 2.5 2.5"/><path d="M6.404 12.768a2 2 0 1 1-2.829-2.829l1.768-1.767a2 2 0 1 1-2.828-2.829l2.828-2.828a2 2 0 1 1 2.829 2.828l1.767-1.768a2 2 0 1 1 2.829 2.829z"/>',
        'house' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'monitor' => '<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/>',
        'gamepad' => '<line x1="6" x2="10" y1="11" y2="11"/><line x1="8" x2="8" y1="9" y2="13"/><line x1="15" x2="15.01" y1="12" y2="12"/><line x1="18" x2="18.01" y1="10" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/>',
        'car' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/>',
        'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'menu' => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'copy' => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'shopping-bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'flame' => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>',
    ];

    public static function icon(string $name, int $size = 16, string $class = '', float $stroke = 2): HtmlString
    {
        $paths = self::ICONS[$name] ?? self::ICONS['tag'];
        $class = trim('ot-icon '.$class);

        return new HtmlString(
            '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round" class="'.$class.'" aria-hidden="true">'.$paths.'</svg>'
        );
    }

    /**
     * Wraps the highlighted tail of a heading in an accent span. A newline marks
     * the split explicitly; otherwise the last $words words are highlighted.
     */
    public static function accent(?string $text, int $words = 1, bool $lineBreak = false): HtmlString
    {
        $text = trim((string) $text);

        if ($text === '') {
            return new HtmlString('');
        }

        if (str_contains($text, "\n")) {
            $pos = strrpos($text, "\n");
            $head = rtrim(substr($text, 0, $pos));
            $tail = trim(substr($text, $pos + 1));

            return new HtmlString(e($head).'<br><span class="ot-accent">'.e($tail).'</span>');
        }

        $parts = preg_split('/\s+/u', $text) ?: [$text];

        if (count($parts) < 2) {
            return new HtmlString('<span class="ot-accent">'.e($text).'</span>');
        }

        $take = min(max(1, $words), count($parts) - 1);
        $head = implode(' ', array_slice($parts, 0, -$take));
        $tail = implode(' ', array_slice($parts, -$take));

        return new HtmlString(e($head).($lineBreak ? '<br>' : ' ').'<span class="ot-accent">'.e($tail).'</span>');
    }

    /** @return array{ink: string, solid: string, tint: string, line: string} */
    public static function palette(?string $seed): array
    {
        return self::PALETTES[crc32((string) $seed) % count(self::PALETTES)];
    }

    /** @return array{icon: ?string, color: string} */
    public static function categoryStyle(?Category $category, int $index = 0): array
    {
        $haystack = strtolower(($category?->slug ?? '').' '.($category?->name ?? ''));

        foreach (self::CATEGORY_STYLES as $pattern => [$icon, $color]) {
            if (preg_match('/'.$pattern.'/', $haystack)) {
                return ['icon' => $icon, 'color' => $color];
            }
        }

        return ['icon' => null, 'color' => self::FALLBACK_COLORS[$index % count(self::FALLBACK_COLORS)]];
    }

    public static function rgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = array_map('hexdec', str_split(strlen($hex) === 3 ? preg_replace('/(.)/', '$1$1', $hex) : $hex, 2));

        return "rgba({$r},{$g},{$b},{$alpha})";
    }

    public static function blogGlow(int $index): string
    {
        return self::BLOG_GLOWS[$index % count(self::BLOG_GLOWS)];
    }

    public static function postCategory(Post $post): string
    {
        $store = $post->relationLoaded('store') ? $post->store : null;

        return $store?->category?->name ?? 'Guide';
    }

    /** Plain-text store blurb for cards, without image captions/alt text. */
    public static function storeSummary(Store $store, int $limit = 160): string
    {
        $html = preg_replace(
            '/<figure\b[^>]*>.*?<\/figure>|<figcaption\b[^>]*>.*?<\/figcaption>|<img\b[^>]*>/is',
            ' ',
            (string) $store->description
        );
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($text === '') {
            $text = 'Verified coupons, promo codes and deals from '.$store->name.'.';
        }

        return \Illuminate\Support\Str::limit($text, $limit);
    }

    /** Repeat items until the marquee has enough cards to fill wide screens. */
    public static function marqueeItems(Collection $items, int $min = 10): Collection
    {
        if ($items->isEmpty()) {
            return $items;
        }

        $result = $items->values();
        while ($result->count() < $min) {
            $result = $result->concat($items);
        }

        return $result;
    }

    /**
     * Discount panel content for coupon rows.
     *
     * @return array{kind: string, value: string, suffix: string}
     */
    public static function discount(Coupon $coupon): array
    {
        $ticket = $coupon->ticketDisplay();

        return match ($ticket['kind']) {
            'percent' => ['kind' => 'percent', 'value' => (int) $ticket['percent'].'%', 'suffix' => 'OFF'],
            'fixed' => ['kind' => 'fixed', 'value' => '$'.number_format($ticket['fixed'], 0, '.', ','), 'suffix' => 'OFF'],
            'free_shipping' => ['kind' => 'free', 'value' => 'FREE', 'suffix' => 'SHIPPING'],
            default => ['kind' => 'deal', 'value' => $coupon->code ? 'CODE' : 'DEAL', 'suffix' => ''],
        };
    }

    /** Short label for hero ticker chips, e.g. "10% Off" or "Get Deal". */
    public static function chipLabel(Coupon $coupon): string
    {
        $discount = self::discount($coupon);

        return match ($discount['kind']) {
            'percent', 'fixed' => $discount['value'].' Off',
            'free' => 'Free Shipping',
            default => $coupon->code ? 'Get Code' : 'Get Deal',
        };
    }

    /** Last characters of a code, shown at the end of the "Show Coupon" button. */
    public static function codeTail(Coupon $coupon, int $chars = 3): string
    {
        $code = (string) ($coupon->code ?? '');
        $length = mb_strlen($code);

        if ($length === 0) {
            return '';
        }

        $visible = min($chars, max(1, $length - 1));

        return str_repeat('•', min(4, $length - $visible)).mb_substr($code, -$visible);
    }

    /** Pseudo-random but stable animation offset so rainbow buttons don't pulse in sync. */
    public static function rainbowDelay(int|string $seed): string
    {
        return '-'.number_format((crc32((string) $seed) % 50) / 10, 1).'s';
    }

    /**
     * @return array{coupons: int, stores: int, categories: int, codes: int, posts: int}
     */
    public static function stats(): array
    {
        try {
            return Cache::remember('ontopcoupon.stats', now()->addMinutes(10), fn () => [
                'coupons' => Coupon::valid()->count(),
                'stores' => Store::active()->count(),
                'categories' => Category::active()->count(),
                'codes' => Coupon::valid()->where('type', 'coupon')->count(),
                'posts' => Post::published()->count(),
            ]);
        } catch (Throwable) {
            return ['coupons' => 0, 'stores' => 0, 'categories' => 0, 'codes' => 0, 'posts' => 0];
        }
    }

    /** @return Collection<int, Store> */
    public static function categoryStores(Category $category, int $limit = 9): Collection
    {
        return Store::active()
            ->where('category_id', $category->id)
            ->with(['coupons' => fn ($q) => $q->valid()->where('show_on_store', true)
                ->select(['id', 'store_id', 'title', 'type', 'discount_type', 'discount_value'])])
            ->orderByDesc('is_pinned_home')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take($limit)
            ->get();
    }

    public static function categoryTopPercent(Category $category): int
    {
        return (int) Cache::remember('ontopcoupon.category.'.$category->id.'.top-percent', now()->addMinutes(10), function () use ($category) {
            return (int) $category->coupons()
                ->valid()
                ->get(['coupons.title', 'coupons.type', 'coupons.discount_type', 'coupons.discount_value'])
                ->max(fn (Coupon $c) => $c->ticketDisplay()['percent']);
        });
    }
}
