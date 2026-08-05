<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;

final class SiteBranding
{
    private const LOGO_KEY = 'site_logo';

    private const NAME_KEY = 'site_display_name';

    private const TAGLINE_KEY = 'site_display_tagline';

    private const HOME_H1_KEY = 'site_home_h1';

    private const HOME_SUB_H1_KEY = 'site_home_sub_h1';

    private const HERO_SUBTITLE_KEY = 'site_home_hero_subtitle';

    private const FOOTER_TAGLINE_KEY = 'site_footer_tagline';

    private const FOOTER_SUB_TAGLINE_KEY = 'site_footer_sub_tagline';

    private const FOOTER_DESCRIPTION_TAGLINE_KEY = 'site_footer_description_tagline';

    private const CONTACT_EMAIL_KEY = 'site_contact_email';

    private const PRIVACY_EMAIL_KEY = 'site_privacy_email';

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

    public static function customName(): ?string
    {
        $value = trim((string) SiteSetting::get(self::NAME_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function customTagline(): ?string
    {
        $value = trim((string) SiteSetting::get(self::TAGLINE_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function hasCustomBrandText(): bool
    {
        return self::customName() !== null || self::customTagline() !== null;
    }

    public static function homeH1(): ?string
    {
        $value = trim((string) SiteSetting::get(self::HOME_H1_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function homeSubH1(): ?string
    {
        $value = trim((string) SiteSetting::get(self::HOME_SUB_H1_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function heroSubtitle(): ?string
    {
        $value = trim((string) SiteSetting::get(self::HERO_SUBTITLE_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function footerTagline(): ?string
    {
        $value = trim((string) SiteSetting::get(self::FOOTER_TAGLINE_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function footerSubTagline(): ?string
    {
        $value = trim((string) SiteSetting::get(self::FOOTER_SUB_TAGLINE_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function footerDescriptionTagline(): ?string
    {
        $value = trim((string) SiteSetting::get(self::FOOTER_DESCRIPTION_TAGLINE_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function resolvedName(): string
    {
        return self::customName() ?? (string) config('site.name');
    }

    public static function resolvedTagline(): string
    {
        return self::customTagline() ?? (string) config('site.tagline');
    }

    public static function resolvedHomeH1(): string
    {
        return self::homeH1() ?? self::resolvedName();
    }

    public static function resolvedHomeSubH1(): string
    {
        return self::homeSubH1() ?? self::resolvedTagline();
    }

    public static function resolvedHeroSubtitle(): string
    {
        return self::heroSubtitle()
            ?? ('Deals for brands like Amazon, Walmart, Target, and other U.S. retailers. '
                .self::resolvedName().' is not affiliated with these merchants.');
    }

    public static function resolvedFooterTagline(): string
    {
        return self::footerTagline() ?? self::resolvedTagline();
    }

    public static function resolvedFooterSubTagline(): string
    {
        return self::footerSubTagline() ?? self::resolvedTagline();
    }

    public static function resolvedFooterDescriptionTagline(): string
    {
        return self::footerDescriptionTagline() ?? self::resolvedTagline();
    }

    public static function contactEmail(): ?string
    {
        $value = trim((string) SiteSetting::get(self::CONTACT_EMAIL_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function privacyEmail(): ?string
    {
        $value = trim((string) SiteSetting::get(self::PRIVACY_EMAIL_KEY, ''));

        return $value !== '' ? $value : null;
    }

    public static function resolvedContactEmail(): string
    {
        return self::contactEmail() ?? (string) config('site.contact_email');
    }

    public static function resolvedPrivacyEmail(): string
    {
        return self::privacyEmail() ?? (string) config('site.privacy_email');
    }

    public static function setCustomName(?string $name): void
    {
        SiteSetting::set(self::NAME_KEY, trim((string) $name));
    }

    public static function setCustomTagline(?string $tagline): void
    {
        SiteSetting::set(self::TAGLINE_KEY, trim((string) $tagline));
    }

    public static function setHomeH1(?string $value): void
    {
        SiteSetting::set(self::HOME_H1_KEY, trim((string) $value));
    }

    public static function setHomeSubH1(?string $value): void
    {
        SiteSetting::set(self::HOME_SUB_H1_KEY, trim((string) $value));
    }

    public static function setHeroSubtitle(?string $value): void
    {
        SiteSetting::set(self::HERO_SUBTITLE_KEY, trim((string) $value));
    }

    public static function setFooterTagline(?string $value): void
    {
        SiteSetting::set(self::FOOTER_TAGLINE_KEY, trim((string) $value));
    }

    public static function setFooterSubTagline(?string $value): void
    {
        SiteSetting::set(self::FOOTER_SUB_TAGLINE_KEY, trim((string) $value));
    }

    public static function setFooterDescriptionTagline(?string $value): void
    {
        SiteSetting::set(self::FOOTER_DESCRIPTION_TAGLINE_KEY, trim((string) $value));
    }

    public static function setContactEmail(?string $value): void
    {
        SiteSetting::set(self::CONTACT_EMAIL_KEY, strtolower(trim((string) $value)));
    }

    public static function setPrivacyEmail(?string $value): void
    {
        SiteSetting::set(self::PRIVACY_EMAIL_KEY, strtolower(trim((string) $value)));
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
