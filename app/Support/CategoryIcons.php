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
            ['value' => '💰', 'label' => 'Savings'],
            ['value' => '🎉', 'label' => 'Promotions'],
            ['value' => '⭐', 'label' => 'Featured'],
            ['value' => '👗', 'label' => 'Fashion'],
            ['value' => '👕', 'label' => 'Apparel'],
            ['value' => '👔', 'label' => 'Formal wear'],
            ['value' => '👟', 'label' => 'Shoes'],
            ['value' => '🩱', 'label' => 'Swimwear'],
            ['value' => '🧥', 'label' => 'Outerwear'],
            ['value' => '👒', 'label' => 'Accessories'],
            ['value' => '👜', 'label' => 'Bags'],
            ['value' => '💎', 'label' => 'Jewelry'],
            ['value' => '💍', 'label' => 'Rings & watches'],
            ['value' => '👓', 'label' => 'Eyewear'],
            ['value' => '📱', 'label' => 'Electronics'],
            ['value' => '📲', 'label' => 'Phone accessories'],
            ['value' => '💻', 'label' => 'Computers'],
            ['value' => '⌨️', 'label' => 'Computer accessories'],
            ['value' => '🎮', 'label' => 'Gaming'],
            ['value' => '🕹️', 'label' => 'Esports'],
            ['value' => '📷', 'label' => 'Cameras'],
            ['value' => '🎧', 'label' => 'Audio'],
            ['value' => '🔌', 'label' => 'Gadgets'],
            ['value' => '💄', 'label' => 'Beauty'],
            ['value' => '💅', 'label' => 'Nails'],
            ['value' => '🧴', 'label' => 'Skincare'],
            ['value' => '🧖', 'label' => 'Spa & wellness'],
            ['value' => '💇', 'label' => 'Hair & Salon'],
            ['value' => '🍔', 'label' => 'Food & Dining'],
            ['value' => '🍕', 'label' => 'Restaurants'],
            ['value' => '☕', 'label' => 'Coffee & Drinks'],
            ['value' => '🥤', 'label' => 'Beverages'],
            ['value' => '🛍️', 'label' => 'Groceries'],
            ['value' => '🍰', 'label' => 'Bakery & sweets'],
            ['value' => '✈️', 'label' => 'Travel'],
            ['value' => '🏨', 'label' => 'Hotels'],
            ['value' => '🧳', 'label' => 'Luggage'],
            ['value' => '🚗', 'label' => 'Auto'],
            ['value' => '🚙', 'label' => 'Vehicles'],
            ['value' => '🔧', 'label' => 'Auto service'],
            ['value' => '⛽', 'label' => 'Gas & auto care'],
            ['value' => '🏠', 'label' => 'Home'],
            ['value' => '🛋️', 'label' => 'Furniture'],
            ['value' => '🪑', 'label' => 'Home office'],
            ['value' => '🛏️', 'label' => 'Bedding'],
            ['value' => '🌿', 'label' => 'Garden'],
            ['value' => '🪴', 'label' => 'Plants'],
            ['value' => '🧹', 'label' => 'Houseware'],
            ['value' => '🎄', 'label' => 'Decorations'],
            ['value' => '🔨', 'label' => 'Tools & DIY'],
            ['value' => '💊', 'label' => 'Health'],
            ['value' => '🏥', 'label' => 'Pharmacy'],
            ['value' => '🏋️', 'label' => 'Fitness'],
            ['value' => '⚽', 'label' => 'Sports'],
            ['value' => '🎿', 'label' => 'Outdoor sports'],
            ['value' => '🎁', 'label' => 'Gifts'],
            ['value' => '🧸', 'label' => 'Toys'],
            ['value' => '👶', 'label' => 'Baby'],
            ['value' => '🧒', 'label' => 'Kids'],
            ['value' => '🐾', 'label' => 'Pets'],
            ['value' => '🐶', 'label' => 'Dogs'],
            ['value' => '🐱', 'label' => 'Cats'],
            ['value' => '📚', 'label' => 'Books'],
            ['value' => '🎓', 'label' => 'Education'],
            ['value' => '✏️', 'label' => 'School supplies'],
            ['value' => '🎨', 'label' => 'Arts & crafts'],
            ['value' => '🎵', 'label' => 'Music'],
            ['value' => '🎬', 'label' => 'Entertainment'],
            ['value' => '📺', 'label' => 'Streaming & TV'],
            ['value' => '🎟️', 'label' => 'Events & tickets'],
            ['value' => '💳', 'label' => 'Finance'],
            ['value' => '🏦', 'label' => 'Banking'],
            ['value' => '💼', 'label' => 'Office & Business'],
            ['value' => '🏢', 'label' => 'For businesses'],
            ['value' => '🖨️', 'label' => 'Office supplies'],
            ['value' => '💾', 'label' => 'Software'],
            ['value' => '☁️', 'label' => 'Cloud services'],
            ['value' => '🌸', 'label' => 'Lifestyle'],
            ['value' => '🌎', 'label' => 'Global retail'],
            ['value' => '🔞', 'label' => 'Adult 18+'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::options(), 'value');
    }

    public static function isEmoji(?string $icon): bool
    {
        return filled($icon) && in_array($icon, self::values(), true);
    }

    public static function isUploadedIcon(?string $icon): bool
    {
        return PublicImage::isStored($icon) && PublicImage::isValidImage($icon);
    }

    public static function isAllowed(?string $icon): bool
    {
        return $icon === null
            || $icon === ''
            || self::isEmoji($icon)
            || self::isUploadedIcon($icon);
    }
}
