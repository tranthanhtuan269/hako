document.addEventListener('DOMContentLoaded', function () {
    const config = window.__scrollCouponPopup;
    const modal = document.getElementById('scroll-coupon-popup');

    if (!config || !modal || !Array.isArray(config.coupons) || config.coupons.length === 0) {
        return;
    }

    const storageKey = 'scroll-coupon-popup-' + config.storeSlug;
    let shown = false;
    let affiliateOpened = false;
    const scrollThreshold = 35;

    function redirectsOnClose() {
        const flow = window.__couponRedirectFlow || 'close';

        return flow === 'close' || flow === 'both';
    }

    function redirectsOnPopupOpen() {
        return (window.__couponRedirectFlow || 'close') === 'popup';
    }

    function flow4Url(preferred) {
        return window.__couponRedirectAffiliateUrl || preferred || config.affiliateUrl || '';
    }

    function getScrollPercent() {
        const doc = document.documentElement;
        const scrollTop = window.scrollY || doc.scrollTop;
        const scrollHeight = doc.scrollHeight - doc.clientHeight;

        if (scrollHeight <= 0) {
            return 0;
        }

        return (scrollTop / scrollHeight) * 100;
    }

    function openBackgroundTab(url) {
        if (typeof window.openBackgroundTab === 'function') {
            window.openBackgroundTab(url);

            return;
        }

        if (!url) {
            return;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    }

    function openAffiliateTab(url) {
        const targetUrl = redirectsOnPopupOpen()
            ? flow4Url(url)
            : (url || config.affiliateUrl);

        if (!targetUrl || affiliateOpened) {
            return;
        }

        const opened = typeof window.openBackgroundTab === 'function'
            ? window.openBackgroundTab(targetUrl)
            : window.open(targetUrl, '_blank', 'noopener,noreferrer');

        if (opened) {
            affiliateOpened = true;
        }
    }

    function showPopup() {
        if (shown || sessionStorage.getItem(storageKey)) {
            return;
        }

        shown = true;
        sessionStorage.setItem(storageKey, '1');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('scroll-coupon-popup-open');

        if (redirectsOnPopupOpen()) {
            openAffiliateTab();
        }
    }

    function closePopup() {
        if (modal.hidden) {
            return;
        }

        if (redirectsOnClose()) {
            openAffiliateTab();
        }

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('scroll-coupon-popup-open');
    }

    function bindCloseWithBackgroundTab(element) {
        element.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || modal.hidden || !redirectsOnClose()) {
                return;
            }

            openAffiliateTab();
        });

        element.addEventListener('click', closePopup);
    }

    function onScroll() {
        if (shown || sessionStorage.getItem(storageKey)) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        if (getScrollPercent() >= scrollThreshold) {
            showPopup();
        }
    }

    modal.querySelectorAll('[data-scroll-popup-close]').forEach(bindCloseWithBackgroundTab);

    modal.addEventListener('mousedown', function (event) {
        if (event.button !== 0 || modal.hidden || !redirectsOnPopupOpen()) {
            return;
        }

        openAffiliateTab();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closePopup();
        }
    });

    modal.querySelectorAll('[data-scroll-popup-deal]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            openAffiliateTab(element.dataset.openUrl || config.affiliateUrl);
            closePopup();
        });
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
