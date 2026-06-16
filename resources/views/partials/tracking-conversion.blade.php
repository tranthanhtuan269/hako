@php
    $conversion = \App\Support\TrackingScripts::conversionForRequest();
@endphp
@if($conversion)
{!! $conversion['html'] !!}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sendTo = @json($conversion['send_to']);

    if (typeof gtag_report_conversion !== 'function' && !sendTo) {
        return;
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }

        const href = link.getAttribute('href') || '';
        const isShopLink = link.classList.contains('sp-get-deal-btn')
            || link.classList.contains('track-conversion')
            || /\/coupons\/[^/]+\/go(?:[?#]|$)/.test(href);

        if (!isShopLink) {
            return;
        }

        const url = link.href;
        if (!url || url === '#') {
            return;
        }

        event.preventDefault();

        const openInNewTab = link.target === '_blank';

        if (typeof gtag_report_conversion === 'function' && !openInNewTab) {
            gtag_report_conversion(url);
            return;
        }

        if (typeof gtag === 'function' && sendTo) {
            gtag('event', 'conversion', {
                send_to: sendTo,
                event_callback: function () {
                    if (openInNewTab) {
                        window.open(url, '_blank', 'noopener');
                    } else {
                        window.location = url;
                    }
                },
            });
            return;
        }

        if (openInNewTab) {
            window.open(url, '_blank', 'noopener');
        } else {
            window.location = url;
        }
    });
});
</script>
@endpush
@endif
