<?php

namespace App\Support;

use App\Models\SiteSetting;

final class SiteImportSettings
{
    private const ALLOW_REIMPORT_KEY = 'import_allow_reimport_existing_stores';

    /** e.g. "{store_name} Deals" — empty means keep AI/template titles */
    private const BLOG_TITLE_TEMPLATE_KEY = 'import_blog_title_template';

    public static function allowReimportExistingStores(): bool
    {
        return filter_var(SiteSetting::get(self::ALLOW_REIMPORT_KEY, '1'), FILTER_VALIDATE_BOOL);
    }

    public static function setAllowReimportExistingStores(bool $allow): void
    {
        SiteSetting::set(self::ALLOW_REIMPORT_KEY, $allow ? '1' : '0');
    }

    public static function blogTitleTemplate(): string
    {
        return trim((string) SiteSetting::get(self::BLOG_TITLE_TEMPLATE_KEY, ''));
    }

    public static function setBlogTitleTemplate(?string $template): void
    {
        $template = trim((string) $template);
        if ($template === '') {
            SiteSetting::set(self::BLOG_TITLE_TEMPLATE_KEY, '');

            return;
        }

        SiteSetting::set(self::BLOG_TITLE_TEMPLATE_KEY, $template);
    }

    /**
     * Build a forced blog title from the site template, or null when unset.
     */
    public static function formatBlogTitle(?string $storeName): ?string
    {
        $template = self::blogTitleTemplate();
        if ($template === '') {
            return null;
        }

        $name = trim((string) $storeName);
        if ($name === '') {
            $name = 'Store';
        }

        $title = str_replace(
            ['{store_name}', '{store}', '{name}', '{project}', '{project_name}'],
            [$name, $name, $name, $name, $name],
            $template
        );

        // If template has no placeholder, append store name before a literal suffix.
        if (! str_contains($template, '{') && ! str_starts_with(mb_strtolower($title), mb_strtolower($name))) {
            $title = trim($name.' '.$template);
        }

        return trim(preg_replace('/\s+/u', ' ', $title) ?? $title);
    }
}
