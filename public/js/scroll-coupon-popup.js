document.addEventListener('DOMContentLoaded', function () {
    const config = window.__scrollCouponPopup;
    const modal = document.getElementById('scroll-coupon-popup');

    if (!config || !modal || !Array.isArray(config.coupons) || config.coupons.length === 0) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
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

    function openAffiliateTab() {
        if (affiliateOpened || !config.affiliateUrl) {
            return;
        }

        affiliateOpened = true;
        window.open(config.affiliateUrl, '_blank', 'noopener');
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

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('scroll-coupon-popup-open');
        openAffiliateTab();
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

    async function resolveCode(button) {
        const code = button.dataset.code;
        if (code) {
            return code;
        }

        const revealUrl = button.dataset.revealUrl;
        if (!revealUrl || !csrf) {
            return null;
        }

        const response = await fetch(revealUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();
        return data.code || null;
    }

    async function copyCode(button) {
        const code = await resolveCode(button);
        if (!code) {
            const goUrl = button.dataset.goUrl;
            if (goUrl) {
                affiliateOpened = true;
                window.open(goUrl, '_blank', 'noopener');
            }
            return;
        }

        try {
            await navigator.clipboard.writeText(code);
            const original = button.textContent;
            button.textContent = 'Copied!';
            button.classList.add('is-copied');
            setTimeout(function () {
                button.textContent = original;
                button.classList.remove('is-copied');
            }, 2000);
        } catch (error) {
            prompt('Copy this code:', code);
        }
    }

    modal.querySelectorAll('[data-scroll-popup-close]').forEach(function (element) {
        element.addEventListener('click', closePopup);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closePopup();
        }
    });

    modal.querySelectorAll('[data-scroll-popup-deal]').forEach(function (element) {
        element.addEventListener('click', function () {
            affiliateOpened = true;
        });
    });

    modal.querySelectorAll('.scroll-coupon-popup-copy').forEach(function (button) {
        button.addEventListener('click', function () {
            copyCode(button);
        });
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
});
