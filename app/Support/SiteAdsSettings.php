<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteAdsSettings
{
    private const KEY = 'site_ads_settings';

    /** @var list<string> */
    public const STATUSES = ['Enabled', 'Paused'];

    /**
     * @return array{sitelinks: list<array{link_text: string, final_url: string, description_1: string, description_2: string, status: string}>, callouts: list<array{text: string, status: string}>}
     */
    public static function get(): array
    {
        $raw = SiteSetting::get(self::KEY);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($decoded)) {
            return self::defaults();
        }

        return self::normalize($decoded);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function save(array $input): void
    {
        SiteSetting::set(self::KEY, json_encode(self::normalize($input), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{sitelinks: list<array{link_text: string, final_url: string, description_1: string, description_2: string, status: string}>, callouts: list<array{text: string, status: string}>}
     */
    public static function defaults(): array
    {
        return [
            'sitelinks' => [
                [
                    'link_text' => 'About Us',
                    'final_url' => route('pages.about'),
                    'description_1' => 'Learn about '.config('site.name'),
                    'description_2' => '',
                    'status' => 'Enabled',
                ],
                [
                    'link_text' => 'Contact Us',
                    'final_url' => route('pages.contact'),
                    'description_1' => 'Get in touch with our team',
                    'description_2' => '',
                    'status' => 'Enabled',
                ],
                [
                    'link_text' => 'All Coupons',
                    'final_url' => route('coupons.index'),
                    'description_1' => 'Browse verified coupon codes',
                    'description_2' => '',
                    'status' => 'Enabled',
                ],
                [
                    'link_text' => 'Blog',
                    'final_url' => route('blog.index'),
                    'description_1' => 'Savings tips and deal guides',
                    'description_2' => '',
                    'status' => 'Enabled',
                ],
            ],
            'callouts' => [
                ['text' => 'Verified promo codes', 'status' => 'Enabled'],
                ['text' => 'Updated daily', 'status' => 'Enabled'],
                ['text' => 'Top U.S. retailers', 'status' => 'Enabled'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{sitelinks: list<array{link_text: string, final_url: string, description_1: string, description_2: string, status: string}>, callouts: list<array{text: string, status: string}>}
     */
    public static function normalize(array $input): array
    {
        $sitelinks = [];
        $rawSitelinks = is_array($input['sitelinks'] ?? null) ? $input['sitelinks'] : [];

        foreach ($rawSitelinks as $row) {
            if (! is_array($row)) {
                continue;
            }

            $linkText = trim((string) ($row['link_text'] ?? ''));
            $finalUrl = trim((string) ($row['final_url'] ?? ''));

            if ($linkText === '' || $finalUrl === '') {
                continue;
            }

            $sitelinks[] = [
                'link_text' => $linkText,
                'final_url' => $finalUrl,
                'description_1' => trim((string) ($row['description_1'] ?? '')),
                'description_2' => trim((string) ($row['description_2'] ?? '')),
                'status' => self::normalizeStatus($row['status'] ?? 'Enabled'),
            ];
        }

        $callouts = [];
        $rawCallouts = is_array($input['callouts'] ?? null) ? $input['callouts'] : [];

        foreach ($rawCallouts as $row) {
            if (! is_array($row)) {
                continue;
            }

            $text = trim((string) ($row['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $callouts[] = [
                'text' => $text,
                'status' => self::normalizeStatus($row['status'] ?? 'Enabled'),
            ];
        }

        return [
            'sitelinks' => $sitelinks,
            'callouts' => $callouts,
        ];
    }

    private static function normalizeStatus(mixed $value): string
    {
        $value = ucfirst(strtolower(trim((string) $value)));

        return in_array($value, self::STATUSES, true) ? $value : 'Enabled';
    }
}
