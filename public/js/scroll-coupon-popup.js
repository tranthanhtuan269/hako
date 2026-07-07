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
        const targetUrl = url || config.affiliateUrl;

        if (!targetUrl || affiliateOpened) {
            return;
        }

        affiliateOpened = true;
        openBackgroundTab(targetUrl);
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
    }

    function closePopup() {
        if (modal.hidden) {
            return;
        }

        openAffiliateTab();

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('scroll-coupon-popup-open');
    }

    function bindCloseWithBackgroundTab(element) {
        element.addEventListener('mousedown', function (event) {
            if (event.button !== 0 || modal.hidden) {
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

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closePopup();
        }
    });

    modal.querySelectorAll('[data-scroll-popup-deal]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            event.preventDefault();
            openAffiliateTab(element.dataset.openUrl || config.affiliateUrl);
        });
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
