document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('.btn-copy').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const code = btn.dataset.code;
            const revealUrl = btn.dataset.revealUrl;

            if (code) {
                await copyText(code, btn);
                return;
            }

            if (!revealUrl || !csrf) return;

            try {
                const res = await fetch(revealUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.code) {
                    await copyText(data.code, btn);
                } else {
                    alert('This offer has no promo code. Click "Shop Now" for details.');
                }
            } catch (e) {
                alert('Could not retrieve the code. Please try again.');
            }
        });
    });

    async function copyText(text, btn) {
        try {
            await navigator.clipboard.writeText(text);
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

    initStoreAutoplaySlider();
});

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
    let dragMoved = false;
    let dragStartX = 0;
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
        dragMoved = false;
        dragStartX = event.clientX;
        dragStartOffset = dragOffset;
        track.style.transition = 'none';
        viewport.classList.add('is-dragging');
        pauseAutoplay();

        if (viewport.setPointerCapture) {
            viewport.setPointerCapture(event.pointerId);
        }
    }

    function onPointerMove(event) {
        if (!isDragging) {
            return;
        }

        const delta = event.clientX - dragStartX;

        if (Math.abs(delta) > 4) {
            dragMoved = true;
        }

        applyOffset(dragStartOffset - delta, false);
    }

    function onPointerUp(event) {
        if (!isDragging) {
            return;
        }

        isDragging = false;
        viewport.classList.remove('is-dragging');

        if (viewport.releasePointerCapture) {
            try {
                viewport.releasePointerCapture(event.pointerId);
            } catch (e) {
                // Ignore if capture was already released.
            }
        }

        goTo(indexFromOffset(dragOffset));
        scheduleResume(5000);
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
        dragMoved = false;
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
