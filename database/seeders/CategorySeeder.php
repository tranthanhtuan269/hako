<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Adult 18 and over', 'icon' => '🔞', 'sort_order' => 1],
            ['name' => 'Arts & crafts', 'icon' => '🎨', 'sort_order' => 2],
            ['name' => 'Babies and kids', 'icon' => '👶', 'sort_order' => 3],
            ['name' => 'Beauty and fragrance', 'icon' => '💄', 'sort_order' => 4],
            ['name' => 'Bedding', 'icon' => '🛏️', 'sort_order' => 5],
            ['name' => 'Books', 'icon' => '📚', 'sort_order' => 6],
            ['name' => 'Clothing accessories', 'icon' => '👒', 'sort_order' => 7],
            ['name' => 'Computers and accessories', 'icon' => '💻', 'sort_order' => 8],
            ['name' => 'Decorations', 'icon' => '🎄', 'sort_order' => 9],
            ['name' => 'Drinks', 'icon' => '🥤', 'sort_order' => 10],
            ['name' => 'Education and Training', 'icon' => '🎓', 'sort_order' => 11],
            ['name' => 'Electronics and Technology', 'icon' => '📱', 'sort_order' => 12],
            ['name' => 'Entertainment and media', 'icon' => '🎬', 'sort_order' => 13],
            ['name' => 'Equipment furniture', 'icon' => '🪑', 'sort_order' => 14],
            ['name' => 'Fashion jewelry', 'icon' => '💍', 'sort_order' => 15],
            ['name' => 'Financial services and products', 'icon' => '💳', 'sort_order' => 16],
            ['name' => 'Food', 'icon' => '🍔', 'sort_order' => 17],
            ['name' => 'For businesses', 'icon' => '🏢', 'sort_order' => 18],
            ['name' => 'Gaming and esports', 'icon' => '🎮', 'sort_order' => 19],
            ['name' => 'Hairdressing accessories', 'icon' => '💇', 'sort_order' => 20],
            ['name' => 'Health', 'icon' => '💊', 'sort_order' => 21],
            ['name' => 'Home Garden', 'icon' => '🌿', 'sort_order' => 22],
            ['name' => 'Houseware', 'icon' => '🏠', 'sort_order' => 23],
            ['name' => 'Pets', 'icon' => '🐾', 'sort_order' => 24],
            ['name' => 'Phone accessories', 'icon' => '📲', 'sort_order' => 25],
            ['name' => 'Retail', 'icon' => '🛒', 'sort_order' => 26],
            ['name' => 'Shoes and sandals', 'icon' => '👟', 'sort_order' => 27],
            ['name' => 'Software and services', 'icon' => '💾', 'sort_order' => 28],
            ['name' => 'Sportswear', 'icon' => '👕', 'sort_order' => 29],
            ['name' => 'Toys', 'icon' => '🧸', 'sort_order' => 30],
            ['name' => 'Travel', 'icon' => '✈️', 'sort_order' => 31],
            ['name' => 'Underwear', 'icon' => '🩱', 'sort_order' => 32],
            ['name' => 'Vehicle service', 'icon' => '🔧', 'sort_order' => 33],
            ['name' => 'Vehicles and accessories', 'icon' => '🚗', 'sort_order' => 34],
        ];

        DB::transaction(function () use ($categories) {
            Store::query()->update(['category_id' => null]);
            Category::query()->delete();

            foreach ($categories as $cat) {
                Category::create(array_merge($cat, [
                    'slug' => Str::slug($cat['name']),
                    'is_active' => true,
                ]));
            }
        });
    }
}
