@if(!empty($breadcrumbs))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
@foreach($breadcrumbs as $i => $crumb)
        {
            "@type": "ListItem",
            "position": {{ $i + 1 }},
            "name": @json($crumb['name']),
            "item": @json($crumb['url'] ?? null)
        }@if(!$loop->last),@endif

@endforeach
    ]
}
</script>
@endif
