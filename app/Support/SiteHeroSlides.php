<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class SiteHeroSlides
{
    private const KEY = 'homepage_hero_slides';

    /**
     * @return list<array{id: string, enabled: bool, headline: string, subtitle: string, cta_label: string, cta_url: string, image: ?string}>
     */
    public static function all(): array
    {
        $raw = SiteSetting::get(self::KEY);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($decoded)) {
            return [];
        }

        return self::normalizeList($decoded);
    }

    /**
     * Slides ready for the public homepage slider.
     *
     * @return Collection<int, array{headline: string, subtitle: string, cta_url: string, cta_label: string, image: ?string}>
     */
    public static function forHome(): Collection
    {
        $slides = collect(self::all())
            ->filter(fn (array $slide) => $slide['enabled'] && filled($slide['image']))
            ->values()
            ->map(fn (array $slide) => [
                'headline' => $slide['headline'],
                'subtitle' => $slide['subtitle'],
                'cta_url' => $slide['cta_url'] !== '' ? $slide['cta_url'] : route('coupons.index'),
                'cta_label' => $slide['cta_label'] !== '' ? $slide['cta_label'] : 'Shop Now',
                'image' => self::imageUrl($slide['image']),
            ]);

        return $slides;
    }

    /**
     * @param  list<array<string, mixed>>  $slides
     * @param  array<int, UploadedFile|null>  $uploads
     * @param  array<int, bool>  $removeFlags
     */
    public static function saveFromRequest(array $slides, array $uploads = [], array $removeFlags = []): void
    {
        $existingById = collect(self::all())->keyBy('id');
        $normalized = [];

        foreach (array_values($slides) as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $id = trim((string) ($slide['id'] ?? ''));
            if ($id === '') {
                $id = (string) Str::uuid();
            }

            $previous = $existingById->get($id);
            $image = is_array($previous) ? ($previous['image'] ?? null) : null;

            if (! empty($removeFlags[$index])) {
                if (is_string($image) && $image !== '') {
                    PublicImage::delete($image);
                }
                $image = null;
            }

            $upload = $uploads[$index] ?? null;
            if ($upload instanceof UploadedFile) {
                if (is_string($image) && $image !== '') {
                    PublicImage::delete($image);
                }
                $image = PublicImage::store($upload, 'hero-slides');
            } elseif (filled($slide['image_url'] ?? null) && empty($removeFlags[$index])) {
                $url = trim((string) $slide['image_url']);
                if (preg_match('#^https?://#i', $url)) {
                    $stored = PublicImage::ingestRemote($url, 'hero-slides');
                    if ($stored) {
                        if (is_string($image) && $image !== '' && $image !== $stored) {
                            PublicImage::delete($image);
                        }
                        $image = $stored;
                    }
                }
            }

            $normalized[] = [
                'id' => $id,
                'enabled' => ! empty($slide['enabled']),
                'headline' => trim((string) ($slide['headline'] ?? 'Exclusive Deals')),
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
                'cta_label' => trim((string) ($slide['cta_label'] ?? 'Shop Now')),
                'cta_url' => trim((string) ($slide['cta_url'] ?? '')),
                'image' => $image,
            ];
        }

        // Delete images for slides that were removed from the form.
        $keptIds = collect($normalized)->pluck('id')->all();
        foreach ($existingById as $id => $old) {
            if (in_array($id, $keptIds, true)) {
                continue;
            }
            if (filled($old['image'] ?? null)) {
                PublicImage::delete((string) $old['image']);
            }
        }

        SiteSetting::set(
            self::KEY,
            json_encode(self::normalizeList($normalized), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public static function imageUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = trim((string) $path);

        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return $path;
        }

        return PublicImage::url($path);
    }

    /**
     * @param  mixed  $input
     * @return list<array{id: string, enabled: bool, headline: string, subtitle: string, cta_label: string, cta_url: string, image: ?string}>
     */
    private static function normalizeList(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $slides = [];

        foreach ($input as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            if ($id === '') {
                $id = (string) Str::uuid();
            }

            $image = $row['image'] ?? null;
            $image = is_string($image) && trim($image) !== '' ? trim($image) : null;

            $slides[] = [
                'id' => $id,
                'enabled' => (bool) ($row['enabled'] ?? true),
                'headline' => Str::limit(trim((string) ($row['headline'] ?? 'Exclusive Deals')), 120, ''),
                'subtitle' => Str::limit(trim((string) ($row['subtitle'] ?? '')), 320, ''),
                'cta_label' => Str::limit(trim((string) ($row['cta_label'] ?? 'Shop Now')), 40, ''),
                'cta_url' => Str::limit(trim((string) ($row['cta_url'] ?? '')), 500, ''),
                'image' => $image,
            ];
        }

        return array_values($slides);
    }
}
