<?php

namespace App\Support;

final class DomainContentReplacer
{
    /**
     * Replace the old domain everywhere in text: full URLs and plain mentions.
     *
     * @return array{text: string, count: int}
     */
    public static function replace(string $text, string $oldDomain, string $newDomain): array
    {
        $oldHost = self::normalizeHost($oldDomain);
        $newHost = self::normalizeHost($newDomain);

        if ($oldHost === '' || $newHost === '' || $oldHost === $newHost || $text === '') {
            return ['text' => $text, 'count' => 0];
        }

        $count = 0;
        $quoted = preg_quote($oldHost, '~');

        // 1) URL forms: https://old.com, http://www.old.com, //old.com
        $urlPattern = '~(https?://|//)(www\.)?'.$quoted.'(?=/|\?|\#|:|\s|"|\'|<|>|$)~i';
        $text = preg_replace_callback(
            $urlPattern,
            static function (array $matches) use ($newHost, &$count): string {
                $count++;
                $www = ! empty($matches[2]) ? 'www.' : '';

                return $matches[1].$www.$newHost;
            },
            $text
        ) ?? $text;

        // 2) Bare domain mentions (non-link text), e.g. "Visit old.com today"
        $barePattern = '~(?<![a-z0-9.-])(www\.)?'.$quoted.'(?![a-z0-9.-])~i';
        $text = preg_replace_callback(
            $barePattern,
            static function (array $matches) use ($newHost, &$count): string {
                $count++;
                $www = ! empty($matches[1]) ? 'www.' : '';

                return $www.$newHost;
            },
            $text
        ) ?? $text;

        return ['text' => $text, 'count' => $count];
    }

    public static function normalizeHost(string $input): string
    {
        $input = trim($input);

        if ($input === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $input)) {
            $host = parse_url($input, PHP_URL_HOST);
        } else {
            $host = parse_url('http://'.ltrim($input, '/'), PHP_URL_HOST);
        }

        if (! is_string($host) || $host === '') {
            $host = preg_replace('#/.*$#', '', $input);
            $host = preg_replace('#[?#].*$#', '', $host);
        }

        $host = strtolower(trim((string) $host, " \t\n\r\0\x0B./"));

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    public static function isValidHost(string $input): bool
    {
        $host = self::normalizeHost($input);

        if ($host === '' || strlen($host) > 253) {
            return false;
        }

        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $host);
    }
}
