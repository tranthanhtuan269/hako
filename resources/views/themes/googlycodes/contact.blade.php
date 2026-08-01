@php
    $email = $contactEmail ?? config('site.contact_email');
@endphp

<section class="gc-contact section">
    <div class="container">
        <header class="gc-contact-head">
            <div class="gc-contact-title">
                <span class="gc-contact-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </span>
                <h1>Contact</h1>
            </div>
        </header>

        <div class="gc-contact-layout">
            <div class="gc-contact-info">
                <h2>Let's Connect</h2>
                <p>We would love to speak with you. Feel free to reach out using the below details.</p>

                <a href="mailto:{{ $email }}" class="gc-contact-email">
                    <span class="gc-contact-email-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <span>{{ $email }}</span>
                </a>

                <a href="{{ route('pages.disclaimer') }}" class="gc-contact-imprint">→ Imprint</a>
            </div>

            <div class="gc-contact-form-card">
                <h3>Fill out the form below and we will contact you as soon as possible!</h3>

                <form method="POST" action="{{ route('pages.contact.submit') }}" class="gc-contact-form">
                    @csrf
                    <input type="hidden" name="subject" value="General Question">

                    <div class="gc-contact-field">
                        <label class="visually-hidden" for="gc-contact-name">Your Name</label>
                        <input type="text" id="gc-contact-name" name="name" value="{{ old('name') }}" placeholder="Your Name" required maxlength="100" autocomplete="name">
                        @error('name')<span class="gc-contact-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="gc-contact-field">
                        <label class="visually-hidden" for="gc-contact-email">Your Email</label>
                        <input type="email" id="gc-contact-email" name="email" value="{{ old('email') }}" placeholder="Your Email" required maxlength="255" autocomplete="email">
                        @error('email')<span class="gc-contact-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="gc-contact-field">
                        <label class="visually-hidden" for="gc-contact-message">Message</label>
                        <textarea id="gc-contact-message" name="message" rows="6" placeholder="Message" required maxlength="5000">{{ old('message') }}</textarea>
                        @error('message')<span class="gc-contact-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="gc-contact-consent">
                        <input type="checkbox" name="consent" id="gc-contact-consent" value="1" @checked(old('consent')) required>
                        <label for="gc-contact-consent">
                            I agree to the <a href="{{ route('pages.privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.
                        </label>
                    </div>
                    @error('consent')<span class="gc-contact-error">{{ $message }}</span>@enderror

                    <button type="submit" class="gc-contact-send">
                        Send
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
