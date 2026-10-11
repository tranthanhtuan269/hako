<?php

namespace App\Support;

final class MediaEmbedParser
{
    public static function convert(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // 1. Convert CKEditor / Quill <oembed url="..."></oembed>
        $html = preg_replace_callback(
            '/<figure[^>]*class=["\'][^"\']*media[^"\']*["\'][^>]*>\s*<oembed\s+url=["\']([^"\']+)["\'][^>]*>\s*<\/oembed>\s*<\/figure>|<oembed\s+url=["\']([^"\']+)["\'][^>]*>\s*<\/oembed>/is',
            static function (array $m): string {
                $url = ! empty($m[1]) ? $m[1] : ($m[2] ?? '');
                return self::renderEmbed($url);
            },
            $html
        ) ?: $html;

        // 2. Convert plain URLs on their own paragraph: e.g. <p>https://www.youtube.com/watch?v=...</p>
        $html = preg_replace_callback(
            '/<p>\s*(https?:\/\/(?:www\.)?(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/|vimeo\.com\/)[a-zA-Z0-9_\-\/?=&#]+)\s*<\/p>/is',
            static function (array $m): string {
                return self::renderEmbed($m[1]);
            },
            $html
        ) ?: $html;

        // 3. Ensure responsive container for any unwrapped iframes (YouTube / Vimeo)
        $html = preg_replace_callback(
            '/(<div[^>]*class=["\'][^"\']*responsive-video[^"\']*["\'][^>]*>\s*)?(<iframe\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>.*?<\/iframe>)(\s*<\/div>)?/is',
            static function (array $m): string {
                $hasWrap = ! empty($m[1]);
                $iframe = $m[2];
                $src = $m[3];
                if (preg_match('#(youtube\.com|youtu\.be|vimeo\.com)#i', $src)) {
                    if ($hasWrap) {
                        return $m[0];
                    }
                    return '<div class="video-container responsive-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:1.5rem 0;border-radius:8px;">'
                        . $iframe
                        . '</div>';
                }
                return $m[0];
            },
            $html
        ) ?: $html;

        return $html;
    }

    public static function renderEmbed(string $url): string
    {
        $url = trim($url);

        // YouTube: watch?v=ID, youtu.be/ID, shorts/ID, embed/ID
        if (preg_match('#(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?|shorts)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})#i', $url, $m)) {
            $youtubeId = $m[1];
            return '<div class="video-container responsive-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:1.5rem 0;border-radius:8px;">'
                . '<iframe src="https://www.youtube.com/embed/' . $youtubeId . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>'
                . '</div>';
        }

        // Vimeo: vimeo.com/ID
        if (preg_match('#vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)#i', $url, $m)) {
            $vimeoId = $m[1];
            return '<div class="video-container responsive-video" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:1.5rem 0;border-radius:8px;">'
                . '<iframe src="https://player.vimeo.com/video/' . $vimeoId . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>'
                . '</div>';
        }

        // Direct video link (.mp4, .webm, .ogg)
        if (preg_match('#\.(mp4|webm|ogg|m4v)($|\?)#i', $url)) {
            return '<div class="video-container" style="max-width:100%;margin:1.5rem 0;">'
                . '<video controls preload="metadata" style="width:100%;max-width:100%;border-radius:8px;display:block;">'
                . '<source src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" type="video/mp4">'
                . 'Trình duyệt của bạn không hỗ trợ video.'
                . '</video>'
                . '</div>';
        }

        return '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }
}
