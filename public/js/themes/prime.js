(function () {
    function initFeatureCarousel(root) {
        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-pch-slide]'));
        var dots = Array.prototype.slice.call(root.querySelectorAll('[data-pch-dot]'));
        if (slides.length < 2) {
            return;
        }

        var index = 0;
        var autoplayMs = parseInt(root.getAttribute('data-autoplay') || '5000', 10);
        var timer = null;

        function show(next) {
            index = (next + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                var active = i === index;
                slide.classList.toggle('is-active', active);
                if (active) {
                    slide.removeAttribute('hidden');
                } else {
                    slide.setAttribute('hidden', '');
                }
            });
            dots.forEach(function (dot, i) {
                var active = i === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function stop() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (autoplayMs > 0) {
                timer = setInterval(function () {
                    show(index + 1);
                }, autoplayMs);
            }
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var target = parseInt(dot.getAttribute('data-slide-index') || '0', 10);
                show(target);
                start();
            });
        });

        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);

        show(0);
        start();
    }

    function initMobileMenuBehavior() {
        var header = document.querySelector('.site-header');
        var toggle = document.querySelector('[data-nav-toggle]');
        var nav = document.querySelector('[data-main-nav]');

        if (!header || !toggle || !nav) {
            return;
        }

        var lastY = window.scrollY || 0;
        var mobileMq = window.matchMedia('(max-width: 768px)');

        function isMobile() {
            return mobileMq.matches;
        }

        function setNavOpen(open) {
            nav.classList.toggle('is-open', open);
            header.classList.toggle('is-nav-open', open);
            document.body.classList.toggle('pch-nav-lock', open && isMobile());
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');

            if (open) {
                header.classList.remove('is-scroll-hidden');
            }
        }

        function closeNav() {
            if (nav.classList.contains('is-open')) {
                setNavOpen(false);
            }
        }

        // Keep header open-state in sync with existing toggle handler in app.js
        toggle.addEventListener('click', function () {
            // app.js toggles first in bubble order depending on registration;
            // sync on next frame after app.js click handler.
            window.requestAnimationFrame(function () {
                var open = nav.classList.contains('is-open');
                header.classList.toggle('is-nav-open', open);
                document.body.classList.toggle('pch-nav-lock', open && isMobile());
                if (open) {
                    header.classList.remove('is-scroll-hidden');
                }
            });
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });

        window.addEventListener('scroll', function () {
            if (!isMobile()) {
                header.classList.remove('is-scroll-hidden', 'is-nav-open');
                document.body.classList.remove('pch-nav-lock');
                return;
            }

            var y = window.scrollY || 0;

            if (nav.classList.contains('is-open')) {
                closeNav();
                lastY = y;
                return;
            }

            if (y < 24) {
                header.classList.remove('is-scroll-hidden');
            } else if (y > lastY + 6) {
                header.classList.add('is-scroll-hidden');
            } else if (y < lastY - 6) {
                header.classList.remove('is-scroll-hidden');
            }

            lastY = y;
        }, { passive: true });

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeNav();
                header.classList.remove('is-scroll-hidden', 'is-nav-open');
                document.body.classList.remove('pch-nav-lock');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-pch-feature]').forEach(initFeatureCarousel);
        initMobileMenuBehavior();
    });
})();
