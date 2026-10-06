@php
    use App\Support\OntopcouponTheme as Otc;

    $brandName = $siteDisplayName ?: $siteName;
    $footerColumns = [
        'Quick Links' => [
            ['Coupons', route('coupons.index')],
            ['Stores', route('stores.index')],
            ['Categories', route('categories.index')],
            ['Blog', route('blog.index')],
        ],
        'Company' => [
            ['About Us', route('pages.about')],
            ['Contact', route('pages.contact')],
            ['Authors', route('authors.index')],
        ],
        'Legal' => [
            ['Privacy Policy', route('pages.privacy')],
            ['Terms of Service', route('pages.terms')],
            ['Cookie Policy', route('pages.cookies')],
            ['Disclaimer', route('pages.disclaimer')],
        ],
    ];
@endphp
<footer class="ot-footer">
    <div class="ot-footer-glow" aria-hidden="true"></div>
    <div class="ot-container ot-footer-inner">
        <div class="ot-footer-grid">
            <div class="ot-footer-brand">
                <a href="{{ route('home') }}" class="ot-footer-logo">
                    @if(!empty($siteLogoUrl))
                        <img src="{{ $siteLogoUrl }}" alt="{{ $brandName }}" width="36" height="36">
                    @else
                        <span class="ot-brand-mark">{!! Otc::icon('tag', 18, '', 2.4) !!}</span>
                    @endif
                    <span>{{ $brandName }}</span>
                </a>
                <p>{{ $siteFooterTagline ?: 'Find the best coupons, promo codes, and deals from hundreds of top stores.' }}</p>
                <div class="ot-footer-contact">
                    @if(!empty($contactEmail))
                        <a href="mailto:{{ $contactEmail }}" class="ot-footer-pill">{!! Otc::icon('mail', 15) !!} {{ $contactEmail }}</a>
                    @endif
                    @if(!empty($siteSocialLinks))
                        @include('partials.site-social-links')
                    @endif
                </div>
            </div>
            @foreach($footerColumns as $heading => $links)
                <div class="ot-footer-col">
                    <p class="ot-footer-heading">{{ $heading }}</p>
                    <ul>
                        @foreach($links as [$label, $url])
                            <li><a href="{{ $url }}">{{ $label }} {!! Otc::icon('arrow-up-right', 13) !!}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class="ot-footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $brandName }}. All rights reserved.</p>
            <p class="ot-footer-tag"><span class="ot-footer-dot" aria-hidden="true"></span> Save more every day.</p>
        </div>
    </div>
</footer>
