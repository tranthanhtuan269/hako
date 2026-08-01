(function () {
    function initEventsToggle() {
        const toggle = document.querySelector('[data-gc-events-toggle]');
        const grid = document.getElementById('gc-events-grid');

        if (!toggle || !grid) {
            return;
        }

        toggle.addEventListener('click', function () {
            const hidden = grid.hasAttribute('hidden');

            if (hidden) {
                grid.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                toggle.innerHTML = '<span class="gc-events-caret" aria-hidden="true">⌃</span> Hide Events';
            } else {
                grid.setAttribute('hidden', 'hidden');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '<span class="gc-events-caret is-down" aria-hidden="true">⌃</span> Show Events';
            }
        });
    }

    function initHeroSlider() {
        const root = document.querySelector('[data-gc-slider]');

        if (!root) {
            return;
        }

        const track = root.querySelector('[data-gc-slider-track]');
        const slides = Array.from(root.querySelectorAll('[data-gc-slide]'));
        const dots = Array.from(root.querySelectorAll('[data-gc-slider-dot]'));
        const prev = root.querySelector('[data-gc-slider-prev]');
        const next = root.querySelector('[data-gc-slider-next]');
        const autoplayMs = parseInt(root.getAttribute('data-autoplay') || '5000', 10);

        if (!track || slides.length === 0) {
            return;
        }

        let index = 0;
        let timer = null;

        function goTo(nextIndex) {
            index = (nextIndex + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (-index * 100) + '%)';

            slides.forEach(function (slide, i) {
                const active = i === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            dots.forEach(function (dot, i) {
                const active = i === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function startAutoplay() {
            stopAutoplay();

            if (slides.length < 2 || !autoplayMs) {
                return;
            }

            timer = window.setInterval(function () {
                goTo(index + 1);
            }, autoplayMs);
        }

        function stopAutoplay() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        if (prev) {
            prev.addEventListener('click', function () {
                goTo(index - 1);
                startAutoplay();
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                goTo(index + 1);
                startAutoplay();
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                const target = parseInt(dot.getAttribute('data-gc-slider-dot') || '0', 10);
                goTo(target);
                startAutoplay();
            });
        });

        root.addEventListener('mouseenter', stopAutoplay);
        root.addEventListener('mouseleave', startAutoplay);
        root.addEventListener('focusin', stopAutoplay);
        root.addEventListener('focusout', startAutoplay);

        goTo(0);
        startAutoplay();
    }

    function initTrendingMarquees() {
        const rows = Array.from(document.querySelectorAll('[data-gc-marquee]'));

        if (rows.length === 0) {
            return;
        }

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        rows.forEach(function (row) {
            const duration = parseFloat(row.getAttribute('data-duration') || '45');

            if (duration > 0) {
                row.style.setProperty('--gc-marquee-duration', duration + 's');
            }

            if (reduceMotion) {
                row.classList.add('is-paused');
                return;
            }

            row.addEventListener('mouseenter', function () {
                row.classList.add('is-paused');
            });

            row.addEventListener('mouseleave', function () {
                row.classList.remove('is-paused');
            });

            row.addEventListener('focusin', function () {
                row.classList.add('is-paused');
            });

            row.addEventListener('focusout', function (event) {
                if (!row.contains(event.relatedTarget)) {
                    row.classList.remove('is-paused');
                }
            });
        });
    }

    function initMobileNav() {
        var toggle = document.querySelector('[data-gc-nav-toggle]');
        var nav = document.querySelector('[data-gc-main-nav]');
        if (!toggle || !nav) {
            return;
        }

        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        }

        toggle.addEventListener('click', function () {
            setOpen(!nav.classList.contains('is-open'));
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                setOpen(false);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        window.addEventListener('resize', function () {
            if (window.matchMedia('(min-width: 901px)').matches) {
                setOpen(false);
            }
        });
    }

    initEventsToggle();
    initHeroSlider();
    initTrendingMarquees();
    initMobileNav();
})();
