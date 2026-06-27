<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PublicImage
{
    /** @var list<string> */
    private const RASTER_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico'];

    public const STORE_LOGO_WIDTH = 300;

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (preg_match('#^https?://#i', $path) || str_starts_with($path, '//')) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    public static function isStored(?string $path): bool
    {
        return $path
            && ! preg_match('#^https?://#i', $path)
            && ! str_starts_with($path, '//');
    }

    public static function isRemote(?string $path): bool
    {
        return filled($path)
            && (preg_match('#^https?://#i', $path) || str_starts_with($path, '//'));
    }

    public static function exists(?string $path): bool
    {
        return self::isStored($path)
            && Storage::disk('public')->exists($path);
    }

    public static function isValidImage(?string $path): bool
    {
        if (! self::exists($path)) {
            return false;
        }

        $binary = Storage::disk('public')->get($path);

        if (! is_string($binary) || strlen($binary) < 20) {
            return false;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $detected = self::detectFormat($binary);

        if ($detected === 'svg') {
            return $ext === 'svg';
        }

        if ($detected !== null && ! in_array($ext, [...self::RASTER_EXTENSIONS, 'svg'], true)) {
            return false;
        }

        if ($detected === 'svg' || $ext === 'svg') {
            return str_contains($binary, '<svg');
        }

        if (in_array($ext, self::RASTER_EXTENSIONS, true)) {
            if ($detected !== null && $detected !== $ext && ! ($detected === 'jpg' && in_array($ext, ['jpg', 'jpeg'], true))) {
                return false;
            }

            if ($ext === 'ico') {
                return true;
            }

            if ($detected !== null && in_array($detected, ['jpg', 'png', 'gif', 'webp'], true)) {
                return true;
            }

            if (function_exists('imagecreatefromstring')) {
                return @imagecreatefromstring($binary) !== false;
            }

            return $detected !== null;
        }

        return false;
    }

    public static function store(UploadedFile $file, string $directory): string
    {
        self::ensureDirectory($directory);

        return $file->store($directory, 'public');
    }

    /**
     * Store under stores/{userId}/ (creates folder if missing).
     */
    public static function storeForUser(UploadedFile $file, int|string $userId, string $base = 'stores'): string
    {
        $directory = trim($base, '/') . '/' . $userId;

        return self::store($file, $directory);
    }

    /**
     * Download a remote image to public storage, or return an already stored path.
     * Never returns a remote URL — only a stored path or null.
     */
    public static function ingestRemote(?string $url, string $directory): ?string
    {
        if (! filled($url)) {
            return null;
        }

        if (self::isStored($url)) {
            return self::isValidImage($url) ? $url : null;
        }

        if (! self::isRemote($url)) {
            return null;
        }

        $stored = self::storeFromUrl($url, $directory);

        return ($stored && self::isValidImage($stored)) ? $stored : null;
    }

    /**
     * Try primary URL, then common logo sources — all saved locally.
     */
    public static function ingestStoreLogo(?string $primaryUrl, ?string $domain, int|string $userId): ?string
    {
        $directory = "stores/{$userId}/logos";
        $domain = $domain ? preg_replace('/^www\./', '', $domain) : null;

        $candidates = array_values(array_unique(array_filter([
            $primaryUrl,
            $domain ? "https://{$domain}/apple-touch-icon.png" : null,
            $domain ? "https://logo.clearbit.com/{$domain}" : null,
            $domain ? "https://www.google.com/s2/favicons?domain={$domain}&sz=128" : null,
        ])));

        foreach ($candidates as $url) {
            $stored = self::ingestRemote($url, $directory);

            if ($stored) {
                return self::resizeRasterToWidth($stored, self::STORE_LOGO_WIDTH) ?? $stored;
            }
        }

        return null;
    }

    public static function storeFromUrl(string $url, string $directory): ?string
    {
        $binary = self::downloadBinary($url);

        if (! $binary) {
            return null;
        }

        self::ensureDirectory($directory);

        return self::writeBinary($binary, $directory, self::guessExtension($url, $binary));
    }

    /**
     * Build a blog-friendly featured image with the logo centered on a fixed canvas.
     */
    public static function storeBlogFeaturedFromRemote(?string $url, int|string $userId): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $binary = self::downloadBinary($url);

        if (! $binary) {
            return self::ingestRemote($url, "posts/{$userId}");
        }

        $directory = "posts/{$userId}";
        self::ensureDirectory($directory);

        if (function_exists('imagecreatetruecolor') && self::detectFormat($binary) !== 'svg') {
            $rendered = self::renderFeaturedCanvas($binary, $directory);

            if ($rendered) {
                return $rendered;
            }
        }

        $stored = self::writeBinary($binary, $directory, self::guessExtension($url, $binary));

        return self::isValidImage($stored) ? $stored : null;
    }

    public static function ensureDirectory(string $directory): void
    {
        $path = trim($directory, '/');

        if ($path !== '' && ! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->makeDirectory($path);
        }
    }

    public static function delete(?string $path): void
    {
        if (self::isStored($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function downloadBinary(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'User-Agent' => config('site.bot_user_agent'),
                    'Accept' => 'image/*,*/*',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();

            if (strlen($body) < 32) {
                return null;
            }

            $type = strtolower((string) $response->header('Content-Type', ''));

            if ($type !== ''
                && ! str_starts_with($type, 'image/')
                && ! str_contains($type, 'icon')
                && ! str_contains($type, 'svg')) {
                return null;
            }

            return $body;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function writeBinary(string $binary, string $directory, string $extension): string
    {
        $filename = trim($directory, '/') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($filename, $binary);

        return $filename;
    }

    private static function detectFormat(string $binary): ?string
    {
        $trim = ltrim($binary);

        if (str_starts_with($trim, '<svg') || str_contains($trim, '<svg')) {
            return 'svg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        if (str_starts_with($binary, "\xff\xd8\xff")) {
            return 'jpg';
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }

        if (str_starts_with($trim, 'RIFF') && str_contains(substr($binary, 0, 16), 'WEBP')) {
            return 'webp';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $binary) ?: '';
            finfo_close($finfo);

            return match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
                'image/svg+xml' => 'svg',
                'image/x-icon', 'image/vnd.microsoft.icon' => 'ico',
                default => null,
            };
        }

        return null;
    }

    private static function guessExtension(string $url, string $binary): string
    {
        $detected = self::detectFormat($binary);

        if ($detected !== null) {
            return $detected;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'jpeg') {
            return 'jpg';
        }

        if (in_array($ext, [...self::RASTER_EXTENSIONS, 'svg'], true)) {
            return $ext;
        }

        return 'png';
    }

    /**
     * Scale a stored raster logo to a fixed width (height keeps aspect ratio).
     */
    public static function resizeRasterToWidth(string $path, int $targetWidth): ?string
    {
        if ($targetWidth < 1 || ! self::exists($path) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = Storage::disk('public')->get($path);

        if (! is_string($binary) || $binary === '') {
            return null;
        }

        $format = self::detectFormat($binary);

        if ($format === null || in_array($format, ['svg', 'ico'], true)) {
            return $path;
        }

        $source = @imagecreatefromstring($binary);

        if (! $source) {
            return $path;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return $path;
        }

        $destW = $targetWidth;
        $destH = max(1, (int) round($srcH * ($targetWidth / $srcW)));

        $dest = imagecreatetruecolor($destW, $destH);

        if (in_array($format, ['png', 'gif', 'webp'], true)) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefilledrectangle($dest, 0, 0, $destW, $destH, $transparent);
            imagealphablending($dest, true);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $destW, $destH, $srcW, $srcH);

        $directory = trim(dirname($path), '/');
        $outputExt = in_array($format, ['png', 'gif', 'webp'], true) ? 'png' : 'jpg';
        $filename = $directory . '/logo-' . Str::uuid() . '.' . $outputExt;
        $fullPath = Storage::disk('public')->path($filename);

        $saved = $outputExt === 'png'
            ? imagepng($dest, $fullPath, 8)
            : imagejpeg($dest, $fullPath, 90);

        imagedestroy($source);
        imagedestroy($dest);

        if (! $saved) {
            return $path;
        }

        Storage::disk('public')->delete($path);

        return self::isValidImage($filename) ? $filename : $path;
    }

    private static function renderFeaturedCanvas(string $binary, string $directory): ?string
    {
        $source = @imagecreatefromstring($binary);

        if (! $source) {
            return null;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $canvasW = 1200;
        $canvasH = 420;
        $maxW = 960;
        $maxH = 280;
        $scale = min($maxW / $srcW, $maxH / $srcH, 1);
        $destW = max(1, (int) round($srcW * $scale));
        $destH = max(1, (int) round($srcH * $scale));
        $destX = (int) (($canvasW - $destW) / 2);
        $destY = (int) (($canvasH - $destH) / 2);

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        $bg = imagecolorallocate($canvas, 248, 250, 252);
        imagefilledrectangle($canvas, 0, 0, $canvasW, $canvasH, $bg);
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $source, $destX, $destY, 0, 0, $destW, $destH, $srcW, $srcH);

        $filename = trim($directory, '/') . '/featured-' . Str::uuid() . '.jpg';
        $fullPath = Storage::disk('public')->path($filename);
        imagejpeg($canvas, $fullPath, 88);

        imagedestroy($source);
        imagedestroy($canvas);

        return $filename;
    }
}
