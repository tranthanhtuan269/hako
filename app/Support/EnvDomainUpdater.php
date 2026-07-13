<?php

namespace App\Support;

final class EnvDomainUpdater
{
    /**
     * Update APP_URL, SITE_URL, and SITE_DOMAIN in the project .env file.
     *
     * @return array{updated: bool, keys: list<string>, message: string}
     */
    public static function update(string $newDomain, ?string $envPath = null): array
    {
        $newHost = DomainContentReplacer::normalizeHost($newDomain);
        $path = $envPath ?? base_path('.env');

        if ($newHost === '' || ! DomainContentReplacer::isValidHost($newHost)) {
            return [
                'updated' => false,
                'keys' => [],
                'message' => 'Cannot update .env: new domain is invalid.',
            ];
        }

        if (! is_file($path) || ! is_writable($path)) {
            return [
                'updated' => false,
                'keys' => [],
                'message' => '.env file is missing or not writable.',
            ];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [
                'updated' => false,
                'keys' => [],
                'message' => 'Could not read .env file.',
            ];
        }

        $newUrl = 'https://'.$newHost;
        $keys = [
            'APP_URL' => $newUrl,
            'SITE_URL' => $newUrl,
            'SITE_DOMAIN' => $newHost,
        ];

        $updatedKeys = [];

        foreach ($keys as $key => $value) {
            $pattern = '/^'.preg_quote($key, '/').'=.*/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $key.'='.$value, $contents, 1) ?? $contents;
            } else {
                $contents = rtrim($contents)."\n{$key}={$value}\n";
            }

            $updatedKeys[] = $key;
        }

        if (file_put_contents($path, $contents) === false) {
            return [
                'updated' => false,
                'keys' => [],
                'message' => 'Could not write .env file.',
            ];
        }

        return [
            'updated' => true,
            'keys' => $updatedKeys,
            'message' => 'Updated '.implode(', ', $updatedKeys).' in .env.',
        ];
    }
}
