<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;

final class SiteBranding
{
    private const LOGO_KEY = 'site_logo';

    private const SOCIAL_KEY = 'site_social_links';

    /** @var array<string, array{label: string, placeholder: string}> */
    private const NETWORKS = [
        'facebook' => [
            'label' => 'Facebook',
            'placeholder' => 'https://www.facebook.com/your-page',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'placeholder' => 'https://www.youtube.com/@your-channel',
        ],
        'twitter' => [
            'label' => 'X (Twitter)',
            'placeholder' => 'https://x.com/your-account',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'placeholder' => 'https://www.instagram.com/your-account',
        ],
        'pinterest' => [
            'label' => 'Pinterest',
            'placeholder' => 'https://www.pinterest.com/your-account',
        ],
    ];

    /**
     * @return array<string, array{label: string, placeholder: string}>
     */
    public static function networks(): array
    {
        return self::NETWORKS;
    }

    public static function logoPath(): ?string
    {
        $path = trim((string) SiteSetting::get(self::LOGO_KEY, ''));

        return $path !== '' ? $path : null;
    }

    public static function logoUrl(): ?string
    {
        $path = self::logoPath();

        if (! $path || ! PublicImage::isValidImage($path)) {
            return null;
        }

        return PublicImage::url($path);
    }

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function socialLinks(): array
    {
        $raw = SiteSetting::get(self::SOCIAL_KEY);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        $links = [];

        if (! is_array($decoded)) {
            return [];
        }

        foreach (self::NETWORKS as $key => $meta) {
            $url = trim((string) ($decoded[$key] ?? ''));

            if ($url === '') {
                continue;
            }

            $links[] = [
                'key' => $key,
                'label' => $meta['label'],
                'url' => $url,
            ];
        }

        return $links;
    }

    /**
     * @return array<string, string>
     */
    public static function socialUrls(): array
    {
        $urls = [];

        foreach (self::socialLinks() as $link) {
            $urls[$link['key']] = $link['url'];
        }

        return $urls;
    }

    public static function setLogoFromUpload(UploadedFile $file): void
    {
        self::deleteStoredLogo();
        SiteSetting::set(self::LOGO_KEY, PublicImage::store($file, 'site/branding'));
    }

    public static function setLogoFromUrl(?string $url): void
    {
        if (! filled($url)) {
            return;
        }

        $stored = PublicImage::ingestRemote($url, 'site/branding');

        if ($stored) {
            self::deleteStoredLogo();
            SiteSetting::set(self::LOGO_KEY, $stored);
        }
    }

    public static function removeLogo(): void
    {
        self::deleteStoredLogo();
        SiteSetting::set(self::LOGO_KEY, '');
    }

    /**
     * @param  array<string, mixed>  $urls
     */
    public static function setSocialUrls(array $urls): void
    {
        $normalized = [];

        foreach (array_keys(self::NETWORKS) as $key) {
            $value = trim((string) ($urls[$key] ?? ''));

            if ($value !== '') {
                $normalized[$key] = $value;
            }
        }

        SiteSetting::set(self::SOCIAL_KEY, json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function deleteStoredLogo(): void
    {
        $path = self::logoPath();

        if ($path && PublicImage::isStored($path)) {
            PublicImage::delete($path);
        }
    }
}
