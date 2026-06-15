@extends('layouts.page')

@section('title', 'Cookie Policy')
@section('meta_description', config('site.name') . ' Cookie Policy — types of cookies we use, analytics, and how to manage your preferences.')
@section('canonical', route('pages.cookies'))

@section('page_title', 'Cookie Policy')

@section('page_meta')
    Last updated: {{ config('site.legal_last_updated') }}
@endsection

@section('page_content')
<p>
    This Cookie Policy explains how <strong>{{ $siteName }}</strong> uses cookies and similar tracking
    technologies on <a href="{{ $siteUrl }}">{{ $siteDomain }}</a>. It should be read together with our
    <a href="{{ route('pages.privacy') }}">Privacy Policy</a>.
</p>

<h2>1. What Are Cookies?</h2>
<p>
    Cookies are small text files placed on your device when you visit a website. They help the site remember
    your preferences, understand how you use the site, and support advertising and analytics. Similar technologies
    include pixels, web beacons, local storage, and SDKs.
</p>

<h2>2. Why We Use Cookies</h2>
<p>We use cookies and similar technologies to:</p>
<ul>
    <li>Enable essential site functionality and security</li>
    <li>Remember your preferences and session information</li>
    <li>Measure traffic and analyze how visitors use our Site</li>
    <li>Deliver and measure advertising (where applicable)</li>
    <li>Track affiliate referrals when you click merchant links</li>
</ul>

<h2>3. Types of Cookies We Use</h2>

<h3>3.1 Strictly Necessary Cookies</h3>
<p>
    These cookies are required for the Site to function—for example, security tokens (CSRF protection),
    session management, and load balancing. You cannot opt out of these cookies through our Site because
    the Site may not work properly without them.
</p>

<h3>3.2 Functional Cookies</h3>
<p>
    These cookies remember choices you make (such as language or region) to provide enhanced features.
</p>

<h3>3.3 Analytics Cookies</h3>
<p>
    These cookies help us understand how visitors interact with the Site by collecting information anonymously
    or in aggregated form, such as pages visited and time spent on the Site. We may use services such as
    Google Analytics or comparable tools.
</p>

<h3>3.4 Advertising &amp; Affiliate Cookies</h3>
<p>
    These cookies may be set by us or third-party partners to deliver relevant ads, limit ad frequency,
    measure campaign performance, and attribute sales to affiliate referrals when you visit merchant sites
    after clicking our links.
</p>

<h2>4. Third-Party Cookies</h2>
<p>Third parties that may set cookies when you use our Site include:</p>
<ul>
    <li><strong>Analytics providers</strong> (e.g., Google Analytics)</li>
    <li><strong>Advertising networks</strong> (e.g., Google AdSense or similar, if enabled)</li>
    <li><strong>Affiliate networks</strong> that track referrals to merchant websites</li>
    <li><strong>Social media platforms</strong> (if social sharing features are present)</li>
</ul>
<p>
    These third parties have their own privacy and cookie policies. We encourage you to review them on the
    respective provider’s website.
</p>

<h2>5. Google Services</h2>
<p>
    If we use Google Analytics, Google AdSense, or other Google marketing products, Google may use cookies
    to serve ads based on your prior visits to our Site or other sites. You can learn more and manage
    Google ad personalization at
    <a href="https://adssettings.google.com" target="_blank" rel="noopener noreferrer">Google Ads Settings</a>
    and opt out of Google Analytics via the
    <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener noreferrer">Google Analytics Opt-out Browser Add-on</a>.
</p>

<h2>6. How to Manage Cookies</h2>
<p>You can control cookies in several ways:</p>
<ul>
    <li><strong>Browser settings</strong> — Most browsers let you block or delete cookies. See your browser’s help section for instructions.</li>
    <li><strong>Industry opt-out tools</strong> — Visit <a href="https://optout.aboutads.info" target="_blank" rel="noopener noreferrer">aboutads.info</a> or <a href="https://optout.networkadvertising.org" target="_blank" rel="noopener noreferrer">networkadvertising.org</a> for interest-based advertising opt-outs.</li>
    <li><strong>Mobile devices</strong> — Adjust advertising identifiers in your device settings (iOS/Android).</li>
</ul>
<p>
    Note: Blocking all cookies may affect Site functionality and your ability to use certain features.
</p>

<h2>7. Do Not Track</h2>
<p>
    Some browsers offer a “Do Not Track” (DNT) signal. Because there is no uniform industry standard for
    responding to DNT signals, our Site does not currently respond to DNT signals in a specific way.
    You may still use the cookie management options described above.
</p>

<h2>8. Updates</h2>
<p>
    We may update this Cookie Policy from time to time. Changes will be posted on this page with an updated
    “Last updated” date.
</p>

<h2>9. Contact</h2>
<p>
    Questions about our use of cookies?<br>
    Email: <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>
</p>
@endsection
