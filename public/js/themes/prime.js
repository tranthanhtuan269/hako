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

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-pch-feature]').forEach(initFeatureCarousel);
    });
})();
