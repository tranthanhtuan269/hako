<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlCleaner
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'h5', 'blockquote', 'a', 'img', 'div', 'span', 'hr',
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="utf-8" ?><div id="root">'.$html.'</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');
        if (! $root) {
            return null;
        }

        self::sanitizeNode($root);

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        $clean = trim($clean);

        return $clean !== '' ? $clean : null;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var DOMElement $element */
        $element = $node;
        $tag = strtolower($element->tagName);

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            self::unwrapElement($element);

            return;
        }

        self::sanitizeAttributes($element);

        $children = [];
        foreach ($element->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            self::sanitizeNode($child);
        }
    }

    private static function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = match ($tag) {
            'a' => ['href', 'title', 'target', 'rel'],
            'img' => ['src', 'alt', 'title', 'width', 'height'],
            default => [],
        };

        $toRemove = [];
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->name);
            if (! in_array($name, $allowed, true)) {
                $toRemove[] = $name;
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }

        if ($tag === 'a') {
            $href = $element->getAttribute('href');
            if (! self::isSafeUrl($href)) {
                $element->removeAttribute('href');
            } else {
                $element->setAttribute('rel', 'noopener noreferrer');
                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('target', '_blank');
                }
            }
        }

        if ($tag === 'img') {
            $src = $element->getAttribute('src');
            if (! self::isSafeUrl($src)) {
                $element->parentNode?->removeChild($element);
            }
        }
    }

    private static function isSafeUrl(?string $url): bool
    {
        if (! $url || trim($url) === '') {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return (bool) preg_match('#^https?://#i', $url);
    }

    public static function replaceAnchorHrefs(?string $html, string $href): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        if (! self::isSafeUrl($href)) {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $wrapped = '<?xml encoding="utf-8" ?><div id="root">'.$html.'</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('root');
        if (! $root) {
            return $html;
        }

        $anchors = $root->getElementsByTagName('a');
        for ($i = $anchors->length - 1; $i >= 0; $i--) {
            /** @var DOMElement $anchor */
            $anchor = $anchors->item($i);
            $currentHref = $anchor->getAttribute('href');

            if (! self::isReplaceableLink($currentHref)) {
                continue;
            }

            $anchor->setAttribute('href', $href);
            $anchor->setAttribute('rel', 'nofollow sponsored');
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        $output = trim($output);

        return $output !== '' ? $output : $html;
    }

    private static function isReplaceableLink(?string $url): bool
    {
        if (! $url || trim($url) === '') {
            return false;
        }

        if (str_starts_with(trim($url), '#')) {
            return false;
        }

        if (preg_match('#^(mailto|tel|javascript):#i', trim($url))) {
            return false;
        }

        return self::isSafeUrl($url);
    }

    public static function plainText(?string $html): string
    {
        if (! $html) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    public static function decodeEntities(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function textFromHtml(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $text = strip_tags($html);

        return trim(preg_replace('/\s+/u', ' ', self::decodeEntities($text)));
    }
}
