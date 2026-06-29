document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    function revealCouponCode(btn, code) {
        const container = btn?.closest('[data-code-reveal], .sp-code-split, .coupon-code-split, .code-box-wrap, .scroll-coupon-popup-code, .scroll-coupon-popup-item, .coupon-detail, .sp-coupon-card, .sp-coupon-row');
        const maskEls = container?.querySelectorAll('[data-masked-code]');

        if (!maskEls?.length || !code) {
            return;
        }

        maskEls.forEach(function (maskEl) {
            maskEl.textContent = code;
            maskEl.classList.add('is-revealed');
        });
    }

    async function resolveCouponReveal(btn) {
        if (btn.dataset.code) {
            return {
                code: btn.dataset.code,
                affiliateUrl: btn.dataset.affiliateUrl || btn.dataset.shopUrl || btn.dataset.goUrl || '',
                title: btn.dataset.couponTitle || '',
            };
        }

        const revealUrl = btn.dataset.revealUrl;
        if (!revealUrl || !csrf) {
            return null;
        }

        try {
            const res = await fetch(revealUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            });
            const data = await res.json();

            if (!data.code) {
                return null;
            }

            return {
                code: data.code,
                affiliateUrl: data.affiliate_url || btn.dataset.affiliateUrl || btn.dataset.shopUrl || btn.dataset.goUrl || '',
                title: data.title || btn.dataset.couponTitle || '',
            };
        } catch (e) {
            return null;
        }
    }

    async function resolveCouponCode(btn) {
        const result = await resolveCouponReveal(btn);
        return result?.code || null;
    }

    window.revealCouponCode = revealCouponCode;
    window.resolveCouponCode = resolveCouponCode;
    window.resolveCouponReveal = resolveCouponReveal;

    initCouponRevealModal();

    document.querySelectorAll('[data-share-copy]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const url = btn.dataset.shareCopy;
            if (!url) {
                return;
            }

            let shareText = url;
            if (btn.dataset.shareText) {
                try {
                    shareText = JSON.parse(btn.dataset.shareText);
                } catch (e) {
                    shareText = btn.dataset.shareText;
                }
            }

            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(shareText);
                } else {
                    throw new Error('Clipboard unavailable');
                }
            } catch (e) {
                window.prompt('Copy this text:', shareText);
                return;
            }

            btn.classList.add('is-copied');
            const originalLabel = btn.getAttribute('aria-label');
            btn.setAttribute('aria-label', 'Link copied');

            window.setTimeout(function () {
                btn.classList.remove('is-copied');
                btn.setAttribute('aria-label', originalLabel || 'Copy link');
            }, 2000);
        });
    });

    document.querySelectorAll('[data-share-native]').forEach(function (btn) {
        if (!navigator.share) {
            return;
        }

        btn.hidden = false;

        btn.addEventListener('click', async function () {
            let title = '';
            let text = '';
            let url = '';

            try {
                title = btn.dataset.shareTitle ? JSON.parse(btn.dataset.shareTitle) : '';
                text = btn.dataset.shareText ? JSON.parse(btn.dataset.shareText) : '';
                url = btn.dataset.shareUrl ? JSON.parse(btn.dataset.shareUrl) : '';
            } catch (e) {
                title = btn.dataset.shareTitle || '';
                text = btn.dataset.shareText || '';
                url = btn.dataset.shareUrl || '';
            }

            try {
                await navigator.share({ title: title, text: text, url: url });
            } catch (e) {
                if (e?.name !== 'AbortError') {
                    // Ignore user cancellation.
                }
            }
        });
    });

    initStoreAutoplaySlider();
});

function initCouponRevealModal() {
    const modal = document.getElementById('sp-coupon-modal');
    if (!modal) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const titleEl = document.getElementById('sp-modal-title');
    const subtitleEl = document.getElementById('sp-modal-subtitle');
    const codeEl = document.getElementById('sp-modal-code');
    const codeInlineEl = document.getElementById('sp-modal-code-inline');
    const expiresEl = document.getElementById('sp-modal-expires');
    const shopEl = document.getElementById('sp-modal-shop');
    const okBtn = document.getElementById('sp-modal-ok');
    const copyBtn = document.getElementById('sp-modal-copy');
    let activeCode = '';
    let pendingAffiliateUrl = '';
    let modalWasShown = false;

    function openAffiliateTab(url) {
        if (!url) {
            return;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    }

    async function copyText(text, btn) {
        try {
            await navigator.clipboard.writeText(text);
            if (!btn) {
                return;
            }
            const original = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(function () {
                btn.textContent = original;
                btn.classList.remove('copied');
            }, 2000);
        } catch (e) {
            prompt('Copy this code:', text);
        }
    }

    function openModal(data) {
        activeCode = data.code || '';
        pendingAffiliateUrl = data.affiliateUrl || data.shopUrl || '';
        modalWasShown = true;

        if (titleEl) {
            titleEl.textContent = data.title || data.discount || 'Special Offer';
        }
        if (subtitleEl) {
            subtitleEl.textContent = data.store
                ? 'Valid at ' + data.store + (data.discount ? ' — ' + data.discount : '')
                : 'Paste this code at checkout to save.';
        }
        if (codeEl) {
            codeEl.textContent = activeCode || '—';
        }
        if (codeInlineEl) {
            codeInlineEl.textContent = activeCode || '';
        }
        if (expiresEl) {
            expiresEl.textContent = data.expires || '';
        }
        if (shopEl) {
            shopEl.href = pendingAffiliateUrl || data.shopUrl || '#';
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sp-modal-open');

        if (activeCode) {
            copyText(activeCode, copyBtn);
        }
    }

    function closeModal(openAffiliate) {
        const shouldOpenAffiliate = openAffiliate && modalWasShown && pendingAffiliateUrl;

        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sp-modal-open');

        if (shouldOpenAffiliate) {
            openAffiliateTab(pendingAffiliateUrl);
        }

        activeCode = '';
        pendingAffiliateUrl = '';
        modalWasShown = false;
    }

    modal.querySelectorAll('[data-sp-modal-close]').forEach(function (el) {
        el.addEventListener('click', function () {
            closeModal(true);
        });
    });

    if (okBtn) {
        okBtn.addEventListener('click', function () {
            closeModal(true);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal(true);
        }
    });

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            if (activeCode) {
                copyText(activeCode, copyBtn);
            }
        });
    }

    async function handleRevealClick(btn) {
        if (btn.id === 'sp-modal-copy' || btn.closest('#sp-coupon-modal')) {
            return;
        }

        try {
            let result = null;

            if (typeof window.resolveCouponReveal === 'function') {
                result = await window.resolveCouponReveal(btn);
            } else if (typeof window.resolveCouponCode === 'function') {
                const code = await window.resolveCouponCode(btn);
                if (code) {
                    result = {
                        code: code,
                        affiliateUrl: btn.dataset.affiliateUrl || btn.dataset.shopUrl || btn.dataset.goUrl || '',
                        title: btn.dataset.couponTitle || '',
                    };
                }
            }

            if (!result?.code) {
                alert('Could not retrieve the code. Please try again.');
                return;
            }

            if (typeof window.revealCouponCode === 'function') {
                window.revealCouponCode(btn, result.code);
            }

            const scrollPopup = document.getElementById('scroll-coupon-popup');
            if (scrollPopup && scrollPopup.hidden === false) {
                scrollPopup.hidden = true;
                scrollPopup.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('scroll-coupon-popup-open');
            }

            openModal({
                code: result.code,
                affiliateUrl: result.affiliateUrl,
                title: result.title || btn.dataset.couponTitle || '',
                discount: btn.dataset.couponDiscount || '',
                store: btn.dataset.couponStore || '',
                expires: btn.dataset.couponExpires || '',
                shopUrl: btn.dataset.shopUrl || btn.dataset.goUrl || '',
            });
        } catch (e) {
            alert('Could not retrieve the code. Please try again.');
        }
    }

    document.querySelectorAll('.btn-copy, .sp-code-copy, .scroll-coupon-popup-copy').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            handleRevealClick(btn);
        });
    });

    window.openCouponRevealModal = openModal;
    window.closeCouponRevealModal = closeModal;
}

function initStoreAutoplaySlider() {
    const slider = document.querySelector('.store-slider');
    if (!slider) {
        return;
    }

    const viewport = slider.querySelector('.store-slider-viewport');
    const track = slider.querySelector('.store-scroll-track');
    const dotsHost = slider.querySelector('.store-slider-dots');
    if (!viewport || !track || !dotsHost) {
        return;
    }

    const items = track.querySelectorAll('.store-chip');
    if (items.length <= 1) {
        return;
    }

    const autoplayMs = parseInt(slider.dataset.autoplay, 10) || 3000;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let currentIndex = 0;
    let timer = null;
    let resumeTimer = null;
    let isDragging = false;
    let dragActive = false;
    let dragMoved = false;
    let dragStartX = 0;
    let dragStartY = 0;
    let dragStartOffset = 0;
    let dragOffset = 0;

    function gap() {
        return parseFloat(getComputedStyle(track).gap) || 16;
    }

    function stepSize() {
        return items[0].offsetWidth + gap();
    }

    function slideCount() {
        const step = stepSize();
        const overflow = track.scrollWidth - viewport.clientWidth;

        if (overflow <= 1 || step <= 0) {
            return 1;
        }

        return Math.ceil(overflow / step) + 1;
    }

    function maxIndex() {
        return Math.max(0, slideCount() - 1);
    }

    function maxOffset() {
        return maxIndex() * stepSize();
    }

    function bindDotClicks() {
        dotsHost.querySelectorAll('.store-slider-dot').forEach(function (dot, index) {
            dot.dataset.slideIndex = String(index);
        });
    }

    function buildDots() {
        dotsHost.innerHTML = '';
        const count = slideCount();

        for (let i = 0; i < count; i++) {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'store-slider-dot' + (i === currentIndex ? ' is-active' : '');
            dot.dataset.slideIndex = String(i);
            dot.setAttribute('role', 'tab');
            dot.setAttribute('aria-label', 'Slide ' + (i + 1));
            dot.setAttribute('aria-selected', i === currentIndex ? 'true' : 'false');
            dotsHost.appendChild(dot);
        }
    }

    function updateDots() {
        dotsHost.querySelectorAll('.store-slider-dot').forEach(function (dot, index) {
            const active = index === currentIndex;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function applyOffset(offset, animate) {
        const clamped = Math.max(0, Math.min(offset, maxOffset()));

        track.style.transition = animate === false ? 'none' : 'transform .45s ease';
        track.style.transform = 'translateX(-' + clamped + 'px)';
        dragOffset = clamped;
    }

    function goTo(index, animate) {
        currentIndex = Math.max(0, Math.min(index, maxIndex()));
        applyOffset(currentIndex * stepSize(), animate);
        updateDots();
    }

    function indexFromOffset(offset) {
        const step = stepSize();

        if (step <= 0) {
            return 0;
        }

        return Math.max(0, Math.min(maxIndex(), Math.round(offset / step)));
    }

    function tick() {
        const max = maxIndex();

        if (max <= 0) {
            return;
        }

        goTo(currentIndex >= max ? 0 : currentIndex + 1);
    }

    function startAutoplay() {
        stopAutoplay();

        if (reducedMotion || maxIndex() <= 0) {
            return;
        }

        timer = window.setInterval(tick, autoplayMs);
    }

    function stopAutoplay() {
        if (timer !== null) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    function pauseAutoplay() {
        stopAutoplay();

        if (resumeTimer !== null) {
            window.clearTimeout(resumeTimer);
            resumeTimer = null;
        }
    }

    function scheduleResume(delay) {
        if (resumeTimer !== null) {
            window.clearTimeout(resumeTimer);
        }

        resumeTimer = window.setTimeout(function () {
            resumeTimer = null;
            startAutoplay();
        }, delay);
    }

    function onPointerDown(event) {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }

        isDragging = true;
        dragActive = false;
        dragMoved = false;
        dragStartX = event.clientX;
        dragStartY = event.clientY;
        dragStartOffset = dragOffset;
        pauseAutoplay();
    }

    function onPointerMove(event) {
        if (!isDragging) {
            return;
        }

        const deltaX = event.clientX - dragStartX;
        const deltaY = event.clientY - dragStartY;

        if (!dragActive) {
            if (Math.abs(deltaX) < 12 && Math.abs(deltaY) < 12) {
                return;
            }

            if (Math.abs(deltaX) <= Math.abs(deltaY)) {
                isDragging = false;
                return;
            }

            dragActive = true;
            dragMoved = true;
            track.style.transition = 'none';
            viewport.classList.add('is-dragging');

            if (viewport.setPointerCapture) {
                viewport.setPointerCapture(event.pointerId);
            }
        }

        applyOffset(dragStartOffset - deltaX, false);
    }

    function onPointerUp(event) {
        if (!isDragging) {
            return;
        }

        const wasDrag = dragActive;
        isDragging = false;
        dragActive = false;
        viewport.classList.remove('is-dragging');

        if (viewport.releasePointerCapture) {
            try {
                viewport.releasePointerCapture(event.pointerId);
            } catch (e) {
                // Ignore if capture was already released.
            }
        }

        if (!wasDrag) {
            if (event.pointerType !== 'mouse') {
                const link = event.target.closest('a.store-chip');
                if (link && link.href) {
                    window.location.assign(link.href);
                }
            }

            scheduleResume(5000);
            dragMoved = false;
            return;
        }

        goTo(indexFromOffset(dragOffset));
        scheduleResume(5000);
        window.setTimeout(function () {
            dragMoved = false;
        }, 0);
    }

    function setup() {
        buildDots();
        goTo(Math.min(currentIndex, maxIndex()), false);
        startAutoplay();
    }

    dotsHost.addEventListener('click', function (event) {
        const dot = event.target.closest('.store-slider-dot');
        if (!dot || !dotsHost.contains(dot)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const index = parseInt(dot.dataset.slideIndex, 10);
        if (Number.isNaN(index)) {
            return;
        }

        goTo(index);
        pauseAutoplay();
        scheduleResume(5000);
    });

    viewport.addEventListener('pointerdown', onPointerDown);
    viewport.addEventListener('pointermove', onPointerMove);
    viewport.addEventListener('pointerup', onPointerUp);
    viewport.addEventListener('pointercancel', onPointerUp);

    viewport.addEventListener('click', function (event) {
        if (!dragMoved) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }, true);

    viewport.addEventListener('dragstart', function (event) {
        event.preventDefault();
    });

    viewport.addEventListener('mouseenter', pauseAutoplay);
    viewport.addEventListener('mouseleave', function () {
        if (!isDragging) {
            scheduleResume(400);
        }
    });
    viewport.addEventListener('focusin', pauseAutoplay);
    viewport.addEventListener('focusout', function () {
        scheduleResume(400);
    });

    window.addEventListener('resize', function () {
        buildDots();
        goTo(Math.min(currentIndex, maxIndex()), false);
    });

    bindDotClicks();

    if (document.readyState === 'complete') {
        setup();
    } else {
        window.addEventListener('load', setup, { once: true });
    }
}
