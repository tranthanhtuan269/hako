<section class="sp-newsletter">
    <div class="container sp-newsletter-inner">
        <h2>Join thousands of savvy shoppers</h2>
        <p>Get the best coupon codes and deal alerts delivered to your inbox — free from {{ config('site.domain') }}.</p>
        <form action="{{ route('pages.contact') }}" method="GET" class="sp-newsletter-form">
            <input type="email" name="email" placeholder="Enter your email address" required aria-label="Email address">
            <button type="submit">Sign Up Now</button>
        </form>
    </div>
</section>
