@extends('layouts.page')

@section('title', 'About Us')
@section('meta_description', 'Learn about ' . config('site.name') . ' — ' . config('site.tagline') . '. Our mission, editorial standards, and how we help U.S. shoppers save.')
@section('canonical', route('pages.about'))

@section('page_title', 'About ' . config('site.name'))

@section('page_subtitle')
    {{ config('site.acronym') }} — {{ config('site.tagline') }}
@endsection

@section('page_content')
<p>
    Welcome to <strong>{{ $siteName }}</strong> (<a href="{{ $siteUrl }}">{{ $siteDomain }}</a>),
    your trusted destination for finding verified coupon codes, promo codes, and online discount deals
    from leading U.S. retailers.
</p>

<h2>Who We Are</h2>
<p>
    <strong>{{ $siteName }}</strong> is {{ config('site.tagline') }}. We built this platform
    to help American shoppers save money by bringing together the best publicly available offers in one
    easy-to-browse place—without the clutter or expired codes you often find elsewhere.
</p>

<h2>Our Mission</h2>
<p>
    Our mission is simple: connect shoppers with legitimate savings opportunities at stores they already
    trust. We research, organize, and publish coupon codes and promotional deals across categories including
    fashion, electronics, beauty, food &amp; dining, travel, and health—updated regularly so you can shop smarter.
</p>

<h2>What We Offer</h2>
<ul>
    <li><strong>Coupon codes</strong> — Copy-and-use promo codes for checkout</li>
    <li><strong>Discount deals</strong> — Store promotions, sales, and special offers</li>
    <li><strong>Store pages</strong> — Browse savings by retailer</li>
    <li><strong>Categories</strong> — Find deals by shopping interest</li>
</ul>

<h2>How We Work</h2>
<p>
    {{ $siteName }} aggregates promotional information from retailers and affiliate partners. When you click
    “Shop Now” or use a code on our site, you may be directed to a third-party merchant website. We do not
    sell products directly, process payments, or fulfill orders. All purchases are completed on the
    retailer’s site and are subject to their terms and policies.
</p>
<p>
    Coupon availability, discount amounts, and eligibility requirements can change at any time without notice.
    We encourage you to verify offer details on the merchant’s website before completing a purchase.
</p>

<h2>Transparency &amp; Trust</h2>
<p>
    We believe in honest publishing. Our site may earn commissions through affiliate links when you make a
    qualifying purchase—at no extra cost to you. This helps us maintain {{ $siteDomain }} as a free resource
    for consumers. For more information, please read our
    <a href="{{ route('pages.disclaimer') }}">Disclaimer</a> and
    <a href="{{ route('pages.privacy') }}">Privacy Policy</a>.
</p>

<h2>Contact Us</h2>
<p>
    Questions, partnership inquiries, or feedback? We’d love to hear from you.
    Visit our <a href="{{ route('pages.contact') }}">Contact Us</a> page or email
    <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>.
</p>
@endsection
