<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Support\AffiliateLinkResolver;
use App\Support\PublicImage;
use Illuminate\Console\Command;

class FixStoreLogosCommand extends Command
{
    protected $signature = 'stores:fix-logos {--slug= : Only fix one store slug}';

    protected $description = 'Re-download store logos to local storage (merchant, Clearbit, Google favicon)';

    public function handle(AffiliateLinkResolver $resolver): int
    {
        $query = Store::query();

        if ($slug = $this->option('slug')) {
            $query->where('slug', $slug);
        }

        $stores = $query->get();
        $fixed = 0;
        $failed = 0;

        foreach ($stores as $store) {
            $primary = null;

            if ($store->website) {
                $merchant = $resolver->enrichFromWebsite(
                    $resolver->resolve($store->website),
                    $store->website
                );
                $primary = $merchant['logo'] ?? null;
            }

            if (! $primary && PublicImage::isRemote($store->logo)) {
                $primary = $store->logo;
            }

            $ok = $store->ensureLogoStored($primary);

            if ($ok) {
                $fixed++;
                $this->line("OK  {$store->slug} → {$store->fresh()->logo}");
            } else {
                $failed++;
                $this->warn("FAIL {$store->slug} (no logo stored)");
            }
        }

        $this->info("Done: {$fixed} fixed, {$failed} failed, {$stores->count()} total.");

        return self::SUCCESS;
    }
}
