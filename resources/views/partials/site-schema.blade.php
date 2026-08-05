<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": @json($siteName),
    "url": @json(config('site.url')),
    "description": @json(config('site.default_description')),
    "email": @json($contactEmail)
}
</script>
