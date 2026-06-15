<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Support\PublicImage;
use Illuminate\Database\Seeder;

class StoreLogoSeeder extends Seeder
{
    public function run(): void
    {
        $logos = [
            'amazon' => 'https://logo.clearbit.com/amazon.com',
            'walmart' => 'https://logo.clearbit.com/walmart.com',
            'target' => 'https://logo.clearbit.com/target.com',
            'best-buy' => 'https://logo.clearbit.com/bestbuy.com',
            'uber-eats' => 'https://logo.clearbit.com/ubereats.com',
            'starbucks' => 'https://logo.clearbit.com/starbucks.com',
        ];

        foreach ($logos as $slug => $logoUrl) {
            $store = Store::where('slug', $slug)->first();

            if (! $store) {
                continue;
            }

            $stored = PublicImage::ingestStoreLogo($logoUrl, $store->domain(), $store->user_id ?? 0);
            $store->update(['logo' => $stored]);
        }

        Store::whereNull('logo')->whereNotNull('website')->each(function (Store $store) {
            $store->ensureLogoStored();
        });
    }
}
