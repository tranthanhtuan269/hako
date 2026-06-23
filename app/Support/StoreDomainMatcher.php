<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Str;

final class StoreDomainMatcher
{
    public function hostFromUrl(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $host = parse_url(trim($url), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return $this->normalizeHost($host);
    }

    public function normalizeHost(string $host): string
    {
        return Str::lower(preg_replace('/^www\./', '', trim($host)));
    }

    /**
     * @param  list<string|null>  $domains
     */
    public function findExistingStore(array $domains): ?Store
    {
        $needles = collect($domains)
            ->filter()
            ->map(fn (string $domain) => $this->normalizeHost($domain))
            ->unique()
            ->values();

        if ($needles->isEmpty()) {
            return null;
        }

        return Store::query()
            ->select(['id', 'name', 'slug', 'website', 'affiliate_url', 'user_id'])
            ->get()
            ->first(function (Store $store) use ($needles) {
                $hosts = collect([
                    $this->hostFromUrl($store->website),
                    $this->hostFromUrl($store->affiliate_url),
                ])->filter();

                return $hosts->contains(fn (string $host) => $needles->contains($host));
            });
    }

    /**
     * @return array{id: int, name: string, slug: string, domain: ?string, edit_url: string, public_url: string}
     */
    public function existingStorePayload(Store $store): array
    {
        $domain = $this->hostFromUrl($store->website) ?? $this->hostFromUrl($store->affiliate_url);
        $isAdmin = auth()->user()?->isAdmin() ?? false;

        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'domain' => $domain,
            'edit_url' => $isAdmin
                ? route('admin.stores.edit', $store)
                : route('member.stores.edit', $store),
            'public_url' => route('stores.show', $store->slug),
        ];
    }
}
