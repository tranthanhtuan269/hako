@extends('layouts.page')

@section('title', 'Disclaimer')
@section('meta_description', config('site.name') . ' Disclaimer and FTC affiliate disclosure — accuracy of coupon codes, third-party offers, and liability limits.')
@section('canonical', route('pages.disclaimer'))

@section('page_title', 'Disclaimer & Affiliate Disclosure')

@section('page_meta')
    Last updated: {{ config('site.legal_last_updated') }}
@endsection

@section('page_content')
<p>
    The information on <a href="{{ $siteUrl }}">{{ $siteDomain }}</a> is provided for general informational
    purposes only. Please read this Disclaimer carefully before relying on any content on our Site.
</p>

<h2>1. No Professional Advice</h2>
<p>
    Content on {{ $siteName }} does not constitute financial, legal, tax, or professional advice.
    You should consult appropriate professionals before making decisions based on information found on the Site.
</p>

<h2>2. Coupon &amp; Deal Accuracy</h2>
<p>
    While we work to list accurate and current coupon codes and promotions, we do not warrant that any offer
    is valid, available, or applicable to your purchase. Retailers may change or cancel offers at any time
    without notice. Discount amounts, minimum purchase requirements, product exclusions, and expiration dates
    are determined solely by the merchant.
</p>
<p>
    <strong>You are responsible for verifying all offer terms on the merchant’s website before completing a transaction.</strong>
</p>

<h2>3. Third-Party Merchants</h2>
<p>
    {{ $siteName }} is not affiliated with, endorsed by, or sponsored by any retailer unless expressly stated.
    All trademarks, logos, and brand names belong to their respective owners. We provide links to third-party
    websites for your convenience; we do not control and are not responsible for merchant products, pricing,
    shipping, billing, customer service, or privacy practices.
</p>

<h2>4. Affiliate Disclosure (FTC Compliance)</h2>
<p>
    In accordance with the U.S. Federal Trade Commission (FTC) guidelines on endorsements and testimonials,
    we disclose that <strong>{{ $siteName }}</strong> may earn advertising fees, commissions, or other compensation
    when you click links on our Site and make a purchase (or complete another qualifying action) on a partner
    merchant’s website.
</p>
<p>
    This compensation comes from the merchant or an affiliate network—not from you—and helps us operate
    {{ $siteDomain }} as a free resource. Our editorial content and deal listings are intended to benefit
    consumers; compensation does not influence which merchants we cover, though it may affect placement of
    certain sponsored or featured offers where clearly labeled.
</p>

<h2>5. No Guarantee of Savings</h2>
<p>
    Past savings or example discounts do not guarantee future results. Your actual savings depend on the
    products you purchase, applicable offer terms, taxes, shipping, and other factors outside our control.
</p>

<h2>6. External Links</h2>
<p>
    The Site contains links to external websites. We are not responsible for the content, accuracy, or
    practices of linked sites. Accessing third-party sites is at your own risk.
</p>

<h2>7. Limitation of Liability</h2>
<p>
    To the fullest extent permitted by law, {{ $siteName }}, its owners, and contributors shall not be liable
    for any direct, indirect, incidental, or consequential damages arising from your use of the Site, reliance
    on any offer information, or transactions with third-party merchants—including lost savings, order issues,
    or product defects.
</p>

<h2>8. Google &amp; Advertising Partners</h2>
<p>
    If we display advertisements through Google AdSense or similar programs, those ads may be served based on
    your visits to this and other websites. Google’s use of advertising cookies enables it and its partners
    to serve ads. See our <a href="{{ route('pages.cookies') }}">Cookie Policy</a> and
    <a href="{{ route('pages.privacy') }}">Privacy Policy</a> for more information.
</p>

<h2>9. Changes</h2>
<p>
    We may update this Disclaimer at any time. Continued use of the Site after changes are posted constitutes
    acceptance of the revised Disclaimer.
</p>

<h2>10. Contact</h2>
<p>
    Questions about this Disclaimer or our affiliate relationships?<br>
    <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a> ·
    <a href="{{ route('pages.contact') }}">Contact Us</a>
</p>
@endsection
