@extends('layouts.page')

@section('title', 'Privacy Policy')
@section('meta_description', config('site.name') . ' Privacy Policy — how we collect, use, and protect your data. CCPA/CPRA disclosures for California residents.')
@section('canonical', route('pages.privacy'))

@section('page_title', 'Privacy Policy')

@section('page_meta')
    Last updated: {{ config('site.legal_last_updated') }}
@endsection

@section('page_content')
<p>
    This Privacy Policy describes how <strong>{{ $siteName }}</strong> (“we,” “us,” or “our”) collects, uses,
    discloses, and protects information when you visit <a href="{{ $siteUrl }}">{{ $siteDomain }}</a> (the “Site”)
    or otherwise interact with our services. By using the Site, you agree to the practices described in this policy.
</p>

<h2>1. Information We Collect</h2>

<h3>1.1 Information You Provide</h3>
<p>We may collect information you voluntarily submit, including:</p>
<ul>
    <li>Name and email address when you contact us through our contact form</li>
    <li>Subject and message content of your inquiry</li>
    <li>Any other information you choose to provide</li>
</ul>

<h3>1.2 Information Collected Automatically</h3>
<p>When you access the Site, we and our service providers may automatically collect:</p>
<ul>
    <li>IP address, browser type, device type, and operating system</li>
    <li>Pages viewed, referring/exit URLs, and date/time stamps</li>
    <li>General geographic location (city/state level) derived from IP address</li>
    <li>Cookies, pixels, and similar tracking technologies (see our <a href="{{ route('pages.cookies') }}">Cookie Policy</a>)</li>
</ul>

<h3>1.3 Information from Third Parties</h3>
<p>
    We may receive information from analytics providers, advertising partners, and affiliate networks about
    how users interact with our Site or complete actions on merchant websites after clicking our links.
</p>

<h2>2. How We Use Information</h2>
<p>We use collected information to:</p>
<ul>
    <li>Operate, maintain, and improve the Site</li>
    <li>Display coupon offers and personalize content</li>
    <li>Respond to your inquiries and support requests</li>
    <li>Analyze traffic, measure performance, and prevent fraud or abuse</li>
    <li>Comply with legal obligations and enforce our Terms of Service</li>
    <li>Send administrative communications (we do not sell your personal information)</li>
</ul>

<h2>3. Legal Bases (Where Applicable)</h2>
<p>
    If you are located in a jurisdiction that requires a legal basis for processing, we rely on: (a) your consent;
    (b) performance of a contract or steps prior to entering a contract; (c) legitimate interests in operating
    and improving our services; and (d) compliance with legal obligations.
</p>

<h2>4. How We Share Information</h2>
<p>We may share information with:</p>
<ul>
    <li><strong>Service providers</strong> — Hosting, analytics, security, and email delivery vendors who process data on our behalf</li>
    <li><strong>Affiliate and advertising partners</strong> — When you click outbound links, merchants or networks may receive referral data</li>
    <li><strong>Legal authorities</strong> — When required by law, subpoena, or to protect rights, safety, and security</li>
    <li><strong>Business transfers</strong> — In connection with a merger, acquisition, or sale of assets</li>
</ul>
<p>We do not sell personal information for monetary consideration as defined under applicable U.S. state privacy laws.</p>

<h2>5. Cookies &amp; Tracking</h2>
<p>
    We use cookies and similar technologies for essential site functions, analytics, and advertising.
    You can manage preferences through your browser settings and, where available, our cookie notice.
    Details are in our <a href="{{ route('pages.cookies') }}">Cookie Policy</a>.
</p>

<h2>6. Your Privacy Rights (United States)</h2>
<p>Depending on your state of residence, you may have the right to:</p>
<ul>
    <li>Know what personal information we collect, use, and disclose</li>
    <li>Request access to or deletion of your personal information</li>
    <li>Correct inaccurate personal information</li>
    <li>Opt out of certain processing (including targeted advertising where applicable)</li>
    <li>Not receive discriminatory treatment for exercising privacy rights</li>
</ul>
<p>
    California residents may also have rights under the California Consumer Privacy Act (CCPA), as amended by
    the California Privacy Rights Act (CPRA). To submit a request, email
    <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a> with the subject line “Privacy Request.”
    We will verify your request as required by law.
</p>

<h2>7. Children’s Privacy</h2>
<p>
    The Site is not directed to children under 13 (or 16 where applicable), and we do not knowingly collect
    personal information from children. If you believe we have collected information from a child, please
    contact us and we will delete it promptly.
</p>

<h2>8. Data Security</h2>
<p>
    We implement reasonable administrative, technical, and organizational safeguards designed to protect
    information. No method of transmission over the Internet is 100% secure; we cannot guarantee absolute security.
</p>

<h2>9. Data Retention</h2>
<p>
    We retain personal information only as long as necessary for the purposes described in this policy,
    unless a longer retention period is required by law. Contact form submissions are typically retained
    for up to 24 months unless a longer period is needed for legal or operational reasons.
</p>

<h2>10. Third-Party Links</h2>
<p>
    Our Site contains links to retailer and partner websites. We are not responsible for their privacy
    practices. We encourage you to review the privacy policies of any site you visit.
</p>

<h2>11. International Users</h2>
<p>
    The Site is operated from the United States. If you access the Site from outside the U.S., your information
    may be transferred to and processed in the U.S., where data protection laws may differ from those in your country.
</p>

<h2>12. Changes to This Policy</h2>
<p>
    We may update this Privacy Policy from time to time. The “Last updated” date at the top reflects the most
    recent revision. Material changes will be posted on this page. Continued use of the Site after changes
    constitutes acceptance of the updated policy.
</p>

<h2>13. Contact Us</h2>
<p>
    For privacy questions or to exercise your rights:<br>
    Email: <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a><br>
    Website: <a href="{{ route('pages.contact') }}">{{ $siteUrl }}/contact-us</a>
</p>
@endsection
