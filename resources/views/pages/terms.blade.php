@extends('layouts.page')

@section('title', 'Terms of Service')
@section('meta_description', config('site.name') . ' Terms of Service — rules for using our coupon and deals website, affiliate disclosures, and user responsibilities.')
@section('canonical', route('pages.terms'))

@section('page_title', 'Terms of Service')

@section('page_meta')
    Last updated: {{ config('site.legal_last_updated') }}
@endsection

@section('page_content')
<p>
    These Terms of Service (“Terms”) govern your access to and use of
    <a href="{{ $siteUrl }}">{{ $siteDomain }}</a> (the “Site”), operated by <strong>{{ $siteName }}</strong>
    (“we,” “us,” or “our”). Please read these Terms carefully before using the Site.
</p>

<h2>1. Acceptance of Terms</h2>
<p>
    By accessing or using the Site, you agree to be bound by these Terms and our
    <a href="{{ route('pages.privacy') }}">Privacy Policy</a>,
    <a href="{{ route('pages.cookies') }}">Cookie Policy</a>, and
    <a href="{{ route('pages.disclaimer') }}">Disclaimer</a>, which are incorporated by reference.
    If you do not agree, do not use the Site.
</p>

<h2>2. Description of Service</h2>
<p>
    {{ $siteName }} ({{ config('site.tagline') }}) provides informational content about coupon codes,
    promotional offers, and discounts offered by third-party merchants. We are a publisher and referral service,
    not a retailer. We do not sell goods, process payments, ship products, or handle returns.
</p>

<h2>3. Eligibility</h2>
<p>
    You must be at least 18 years old (or the age of majority in your jurisdiction) to use the Site.
    By using the Site, you represent that you meet this requirement and have the legal capacity to enter into these Terms.
</p>

<h2>4. Coupon &amp; Offer Information</h2>
<p>
    We strive to publish accurate and current offers, but we do not guarantee that any coupon code or promotion
    will work, remain available, or apply to your purchase. Merchants control offer terms, exclusions, and expiration.
    You are solely responsible for verifying offer details on the merchant’s website before checkout.
</p>

<h2>5. Affiliate Links &amp; Compensation</h2>
<p>
    The Site may contain affiliate links. We may earn a commission when you click a link and make a qualifying
    purchase from a merchant, at no additional cost to you. This compensation helps fund our operations.
    See our <a href="{{ route('pages.disclaimer') }}">Disclaimer</a> for more information.
</p>

<h2>6. User Conduct</h2>
<p>You agree not to:</p>
<ul>
    <li>Use the Site for any unlawful purpose or in violation of these Terms</li>
    <li>Scrape, crawl, or harvest data from the Site without our prior written consent</li>
    <li>Attempt to gain unauthorized access to our systems or networks</li>
    <li>Transmit malware, spam, or harmful code</li>
    <li>Impersonate any person or entity or misrepresent your affiliation</li>
    <li>Interfere with the proper functioning of the Site</li>
</ul>

<h2>7. Intellectual Property</h2>
<p>
    The Site and its original content, features, logos, and design are owned by {{ $siteName }} or its licensors
    and are protected by copyright, trademark, and other intellectual property laws. You may not copy, modify,
    distribute, or create derivative works without our express written permission, except for personal,
    non-commercial use as permitted by law.
</p>

<h2>8. Third-Party Websites</h2>
<p>
    The Site links to third-party merchant websites. We do not control and are not responsible for third-party
    content, products, services, policies, or practices. Your interactions with merchants are solely between you
    and the merchant.
</p>

<h2>9. Disclaimer of Warranties</h2>
<p>
    THE SITE IS PROVIDED “AS IS” AND “AS AVAILABLE” WITHOUT WARRANTIES OF ANY KIND, WHETHER EXPRESS OR IMPLIED,
    INCLUDING IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.
    WE DO NOT WARRANT THAT THE SITE WILL BE UNINTERRUPTED, ERROR-FREE, OR FREE OF VIRUSES OR OTHER HARMFUL COMPONENTS.
</p>

<h2>10. Limitation of Liability</h2>
<p>
    TO THE MAXIMUM EXTENT PERMITTED BY LAW, {{ strtoupper($siteName) }} AND ITS OFFICERS, DIRECTORS, EMPLOYEES,
    AND AGENTS SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES,
    OR ANY LOSS OF PROFITS, DATA, OR GOODWILL, ARISING FROM YOUR USE OF THE SITE OR ANY THIRD-PARTY OFFERS,
    EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. OUR TOTAL LIABILITY FOR ANY CLAIM ARISING
    OUT OF THESE TERMS OR THE SITE SHALL NOT EXCEED ONE HUNDRED U.S. DOLLARS ($100).
</p>

<h2>11. Indemnification</h2>
<p>
    You agree to indemnify and hold harmless {{ $siteName }} and its affiliates from any claims, damages, losses,
    liabilities, and expenses (including reasonable attorneys’ fees) arising from your use of the Site, violation
    of these Terms, or infringement of any third-party rights.
</p>

<h2>12. Modifications</h2>
<p>
    We may modify these Terms at any time by posting the updated version on this page with a revised “Last updated”
    date. Your continued use of the Site after changes become effective constitutes acceptance of the revised Terms.
</p>

<h2>13. Governing Law &amp; Dispute Resolution</h2>
<p>
    These Terms are governed by the laws of the United States and the State of Delaware, without regard to
    conflict-of-law principles. Any dispute arising under these Terms shall be resolved in the state or federal
    courts located in Delaware, and you consent to personal jurisdiction in those courts.
</p>

<h2>14. Severability &amp; Waiver</h2>
<p>
    If any provision of these Terms is found unenforceable, the remaining provisions remain in full force.
    Our failure to enforce any right or provision does not constitute a waiver of that right or provision.
</p>

<h2>15. Contact</h2>
<p>
    Questions about these Terms?<br>
    Email: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a><br>
    <a href="{{ route('pages.contact') }}">Contact Us</a>
</p>
@endsection
