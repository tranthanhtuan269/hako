<?php

namespace App\Support;

use Illuminate\Support\Str;

final class MerchantPricingOfferExtractor
{
    /**
     * @return list<array{code: null, title: string, description: ?string, coupon_type: string, discount_label: ?string, expires_at: null, source: string}>
     */
    public function extract(?string $html, ?string $pricingUrl = null): array
    {
        if (! $html) {
            return [];
        }

        $text = $this->normalizeText($html);
        $offers = [];
        $seen = [];

        $freeTrial = $this->extractFreeTrial($text);
        if ($freeTrial !== null) {
            $key = Str::lower($freeTrial['title']);
            $seen[$key] = true;
            $offers[] = $freeTrial;
        }

        foreach ($this->extractSavePercents($text) as $saveOffer) {
            $key = Str::lower($saveOffer['title']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $offers[] = $saveOffer;
        }

        if ($pricingUrl) {
            foreach ($offers as &$offer) {
                $offer['description'] = filled($offer['description'] ?? null)
                    ? $offer['description']
                    : 'Listed on '.$pricingUrl;
            }
            unset($offer);
        }

        return $offers;
    }

    /**
     * Prefer explicit /pricing (and close cousins) when no catalog products exist.
     *
     * @return list<string>
     */
    public function candidateUrls(string $baseUrl, ?string $homepageHtml = null): array
    {
        $origin = $this->origin($baseUrl);
        if ($origin === '') {
            return [];
        }

        $urls = [];

        foreach (['/pricing', '/pricing/', '/plans', '/plans/', '/price', '/price/'] as $path) {
            $urls[] = $origin.$path;
        }

        if ($homepageHtml) {
            if (preg_match_all('#href=["\']([^"\']+)["\']#i', $homepageHtml, $matches)) {
                foreach ($matches[1] as $href) {
                    $href = html_entity_decode(trim($href));
                    if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
                        continue;
                    }

                    if (! preg_match('~(?:^|/)(?:pricing|plans|price)(?:/|$|\?|#)~i', $href)) {
                        continue;
                    }

                    $absolute = $this->absolutize($origin.'/', $href);
                    if ($absolute !== null) {
                        $urls[] = $absolute;
                    }
                }
            }
        }

        $unique = [];
        $seen = [];
        foreach ($urls as $url) {
            $key = Str::lower(rtrim($url, '/'));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $url;
        }

        return $unique;
    }

    /**
     * @return array{code: null, title: string, description: ?string, coupon_type: string, discount_label: ?string, expires_at: null, source: string}|null
     */
    private function extractFreeTrial(string $text): ?array
    {
        if (! preg_match('/\b(?:\d{1,3}[\s-]*day(?:s)?\s+)?free\s+trials?\b/i', $text, $match)) {
            return null;
        }

        $phrase = trim(preg_replace('/\s+/', ' ', $match[0]) ?? $match[0]);
        $description = null;

        if (preg_match('/\b(\d{1,3})[\s-]*day(?:s)?\s+free\s+trial\b/i', $phrase, $days)) {
            $description = ((int) $days[1]).'-day free trial listed on the pricing page';
        } elseif (preg_match('/\bno\s+credit\s+card\s+required\b/i', $text)) {
            $description = 'Free trial listed on the pricing page — no credit card required';
        }

        return $this->offer('Free Trial', $description, 'Free Trial');
    }

    /**
     * @return list<array{code: null, title: string, description: ?string, coupon_type: string, discount_label: ?string, expires_at: null, source: string}>
     */
    private function extractSavePercents(string $text): array
    {
        $offers = [];
        $seenPercents = [];

        // "AnnuallySave 50%" / "Annually · Save 50%" / "Billed annually Save 40%"
        if (preg_match_all(
            '/\b(?:annually|annual|yearly|billed\s+annually|pay\s+annually|per\s+year)\b.{0,40}?\bsave\s+(\d{1,2})\s*%/i',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $percent = (int) $match[1];
                if ($percent < 5 || $percent > 90 || isset($seenPercents[$percent])) {
                    continue;
                }
                $seenPercents[$percent] = true;
                $offers[] = $this->offer(
                    'Save '.$percent.'%',
                    'Annual billing discount listed on the pricing page',
                    'Save '.$percent.'%'
                );
            }
        }

        // "Save 50%" near annual toggle wording even if order is reversed
        if ($offers === [] && preg_match_all(
            '/\bsave\s+(\d{1,2})\s*%.{0,40}?\b(?:annually|annual|yearly|billed\s+annually)\b/i',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $percent = (int) $match[1];
                if ($percent < 5 || $percent > 90 || isset($seenPercents[$percent])) {
                    continue;
                }
                $seenPercents[$percent] = true;
                $offers[] = $this->offer(
                    'Save '.$percent.'%',
                    'Annual billing discount listed on the pricing page',
                    'Save '.$percent.'%'
                );
            }
        }

        // Pricing pages often concatenate "AnnuallySave 50%" without spaces after strip_tags.
        if ($offers === [] && preg_match_all('/annually\s*save\s*(\d{1,2})\s*%/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $percent = (int) $match[1];
                if ($percent < 5 || $percent > 90 || isset($seenPercents[$percent])) {
                    continue;
                }
                $seenPercents[$percent] = true;
                $offers[] = $this->offer(
                    'Save '.$percent.'%',
                    'Annual billing discount listed on the pricing page',
                    'Save '.$percent.'%'
                );
            }
        }

        return $offers;
    }

    /**
     * @return array{code: null, title: string, description: ?string, coupon_type: string, discount_label: ?string, expires_at: null, source: string}
     */
    private function offer(string $title, ?string $description, ?string $discountLabel): array
    {
        return [
            'code' => null,
            'title' => $title,
            'description' => $description,
            'coupon_type' => 'deal',
            'discount_label' => $discountLabel,
            'expires_at' => null,
            'source' => 'pricing',
        ];
    }

    private function normalizeText(string $html): string
    {
        $text = preg_replace('/<(script|style|noscript)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function origin(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'];

        return $scheme.'://'.$host;
    }

    private function absolutize(string $baseUrl, string $path): ?string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if (str_starts_with($path, '//')) {
            $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme.':'.$path;
        }

        $origin = $this->origin($baseUrl);
        if ($origin === '') {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return $origin.$path;
        }

        return rtrim($origin, '/').'/'.ltrim($path, '/');
    }
}
