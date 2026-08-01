@php
    $siteName = config('site.name');
    $copyBlocks = [
        [
            'title' => 'Big Savings on Everything You Desire',
            'body' => "Use our latest {$siteName} discount codes to save on all your purchases from brands like Banana Republic, Bellerose, and Birkenstock. Avoid the pitfalls of clearance sales that can drain your wallet. We're here to ensure you get the best prices without sacrificing quality. We believe in smart shopping and encourage you to save more with us.",
        ],
        [
            'title' => 'Discounts on All Your Favorites',
            'body' => "{$siteName} knows how to keep customers coming back. Our promo codes guarantee you get excellent deals on everything without the worry of negative reviews. Shop confidently, knowing you're getting the best from reputable retailers like Alexander Wang, AllSaints, and ASOS. We are committed to providing the best shopping deals to satisfy our visitors.",
        ],
        [
            'title' => 'Save Money in Various Ways',
            'body' => "When your shopping list becomes too expensive, it's time to seek savings. Connect with {$siteName} to discover incredible deals and shop at your preferred prices. Follow us on social media to stay informed about offers and save money on your favorite brands.",
        ],
        [
            'title' => 'Special Promotions',
            'body' => "Don't miss these limited-time offers and exclusive deals.",
        ],
    ];
@endphp

<section class="gc-seo-copy section">
    <div class="container">
        <div class="gc-seo-blocks">
            @foreach($copyBlocks as $block)
                <article class="gc-seo-block">
                    <h2 class="gc-seo-heading">{{ $block['title'] }}</h2>
                    <p>{{ $block['body'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
