@extends('layouts.page')

@section('title', 'Contact Us')
@section('meta_description', 'Contact ' . config('site.name') . ' for partnership inquiries, coupon submissions, or support. We respond to U.S. shoppers and publishers.')
@section('canonical', route('pages.contact'))

@section('page_title', 'Contact Us')

@section('page_subtitle')
    We typically respond within 2–3 business days.
@endsection

@section('page_content')
<div class="contact-grid">
    <div class="contact-info">
        <h2>Get in Touch</h2>
        <p>
            For general questions, press inquiries, merchant partnerships, or help with an offer listed on
            {{ $siteDomain }}, use the form or contact us directly.
        </p>

        <div class="contact-card">
            <h3>General Inquiries</h3>
            <p><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></p>
        </div>

        <div class="contact-card">
            <h3>Privacy Requests</h3>
            <p>
                For privacy-related questions or data requests (CCPA/CPRA), email
                <a href="mailto:{{ $privacyEmail }}">{{ $privacyEmail }}</a>.
            </p>
        </div>

        @if(!empty($siteSocialLinks))
            <div class="contact-card">
                <h3>Follow Us</h3>
                <p>Connect with {{ $siteName }} on social media.</p>
                @include('partials.site-social-links')
            </div>
        @endif

        <div class="contact-card">
            <h3>Mailing Address</h3>
            <p>
                {{ $siteName }}<br>
                c/o Customer Support<br>
                {{ $siteDomain }}<br>
                United States
            </p>
            <p class="text-muted"><small>Please use email for the fastest response.</small></p>
        </div>

        <h2>Before You Write</h2>
        <ul>
            <li><strong>Coupon not working?</strong> Offers expire frequently—check the retailer’s site for current terms.</li>
            <li><strong>Order issues?</strong> Contact the store where you purchased; we do not process orders.</li>
            <li><strong>Remove a listing?</strong> Merchants may request updates via the form below.</li>
        </ul>
    </div>

    <div class="contact-form-wrap">
        <h2>Send a Message</h2>
        <form method="POST" action="{{ route('pages.contact.submit') }}" class="contact-form">
            @csrf
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100">
                @error('name')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255">
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="subject">Subject *</label>
                <select id="subject" name="subject" required>
                    <option value="">Select a topic</option>
                    <option value="General Question" @selected(old('subject') === 'General Question')>General Question</option>
                    <option value="Coupon / Offer Issue" @selected(old('subject') === 'Coupon / Offer Issue')>Coupon / Offer Issue</option>
                    <option value="Merchant / Partnership" @selected(old('subject') === 'Merchant / Partnership')>Merchant / Partnership</option>
                    <option value="Privacy / Data Request" @selected(old('subject') === 'Privacy / Data Request')>Privacy / Data Request</option>
                    <option value="Report Incorrect Content" @selected(old('subject') === 'Report Incorrect Content')>Report Incorrect Content</option>
                    <option value="Other" @selected(old('subject') === 'Other')>Other</option>
                </select>
                @error('subject')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="6" required maxlength="5000">{{ old('message') }}</textarea>
                @error('message')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="form-check">
                <input type="checkbox" name="consent" id="consent" value="1" @checked(old('consent')) required>
                <label for="consent">
                    I agree to the <a href="{{ route('pages.privacy') }}" target="_blank">Privacy Policy</a>
                    and consent to {{ $siteName }} processing my information to respond to this inquiry. *
                </label>
            </div>
            @error('consent')<span class="field-error">{{ $message }}</span>@enderror
            <button type="submit" class="btn btn-primary" style="width:100%;padding:.75rem;margin-top:.5rem;">Send Message</button>
        </form>
    </div>
</div>
@endsection
