<?php

namespace App\Support;

final class CategoryIcons
{
    /**
     * Curated emoji icons for store categories (coupon / deals site).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => '🛒', 'label' => 'Shopping / General'],
            ['value' => '🔥', 'label' => 'Hot Deals'],
            ['value' => '🏷️', 'label' => 'Coupons & Offers'],
            ['value' => '👗', 'label' => 'Fashion'],
            ['value' => '👟', 'label' => 'Shoes'],
            ['value' => '💎', 'label' => 'Jewelry'],
            ['value' => '👜', 'label' => 'Bags & Accessories'],
            ['value' => '📱', 'label' => 'Electronics'],
            ['value' => '💻', 'label' => 'Computers'],
            ['value' => '🎮', 'label' => 'Gaming'],
            ['value' => '📷', 'label' => 'Cameras'],
            ['value' => '💄', 'label' => 'Beauty'],
            ['value' => '🧴', 'label' => 'Skincare'],
            ['value' => '💇', 'label' => 'Hair & Salon'],
            ['value' => '🍔', 'label' => 'Food & Dining'],
            ['value' => '☕', 'label' => 'Coffee & Drinks'],
            ['value' => '🛍️', 'label' => 'Groceries'],
            ['value' => '✈️', 'label' => 'Travel'],
            ['value' => '🏨', 'label' => 'Hotels'],
            ['value' => '🚗', 'label' => 'Auto'],
            ['value' => '⛽', 'label' => 'Gas & Auto care'],
            ['value' => '🏠', 'label' => 'Home'],
            ['value' => '🛋️', 'label' => 'Furniture'],
            ['value' => '🌿', 'label' => 'Garden'],
            ['value' => '🔧', 'label' => 'Tools & DIY'],
            ['value' => '💊', 'label' => 'Health'],
            ['value' => '🏥', 'label' => 'Pharmacy'],
            ['value' => '🏋️', 'label' => 'Fitness'],
            ['value' => '⚽', 'label' => 'Sports'],
            ['value' => '🎁', 'label' => 'Gifts'],
            ['value' => '🧸', 'label' => 'Toys & Kids'],
            ['value' => '👶', 'label' => 'Baby'],
            ['value' => '🐾', 'label' => 'Pets'],
            ['value' => '📚', 'label' => 'Books & Education'],
            ['value' => '🎵', 'label' => 'Music'],
            ['value' => '📺', 'label' => 'Streaming & TV'],
            ['value' => '💳', 'label' => 'Finance'],
            ['value' => '💼', 'label' => 'Office & Business'],
            ['value' => '🖨️', 'label' => 'Office supplies'],
            ['value' => '👓', 'label' => 'Eyewear'],
            ['value' => '🧥', 'label' => 'Outerwear'],
            ['value' => '🌸', 'label' => 'Lifestyle'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::options(), 'value');
    }

    public static function isAllowed(?string $icon): bool
    {
        return $icon === null || $icon === '' || in_array($icon, self::values(), true);
    }
}
