(function () {
    'use strict';

    function initTabs() {
        document.querySelectorAll('[data-ot-tabs]').forEach(function (root) {
            var tabs = Array.prototype.slice.call(root.querySelectorAll('[data-ot-tab]'));
            var panels = Array.prototype.slice.call(root.querySelectorAll('[data-ot-panel]'));

            function activate(index, focus) {
                tabs.forEach(function (tab, i) {
                    var active = i === index;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.tabIndex = active ? 0 : -1;
                    if (active && focus) {
                        tab.focus();
                    }
                });
                panels.forEach(function (panel, i) {
                    panel.hidden = i !== index;
                });
            }

            tabs.forEach(function (tab, index) {
                tab.addEventListener('click', function () {
                    activate(index, false);
                });
                tab.addEventListener('keydown', function (event) {
                    var next = null;
                    if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
                        next = (index + 1) % tabs.length;
                    } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
                        next = (index - 1 + tabs.length) % tabs.length;
                    }
                    if (next !== null) {
                        event.preventDefault();
                        activate(next, true);
                    }
                });
            });
        });
    }

    function flash(btn, text) {
        var original = btn.getAttribute('aria-label') || '';
        btn.classList.add('is-done');
        btn.setAttribute('data-ot-tip', text);
        setTimeout(function () {
            btn.classList.remove('is-done');
            btn.removeAttribute('data-ot-tip');
            btn.setAttribute('aria-label', original);
        }, 1600);
    }

    function initShare() {
        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-ot-share]');
            if (!btn) {
                return;
            }

            event.preventDefault();
            var url = btn.getAttribute('data-share-url') || window.location.href;
            var title = btn.getAttribute('data-share-title') || document.title;

            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function () {});
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    flash(btn, 'Link copied');
                }, function () {
                    window.prompt('Copy this link', url);
                });
                return;
            }

            window.prompt('Copy this link', url);
        });
    }

    function initDropdowns() {
        var dropdowns = document.querySelectorAll('[data-ot-dropdown]');
        if (!dropdowns.length) {
            return;
        }

        document.addEventListener('click', function (event) {
            dropdowns.forEach(function (dropdown) {
                if (dropdown.open && !dropdown.contains(event.target)) {
                    dropdown.open = false;
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            dropdowns.forEach(function (dropdown) {
                dropdown.open = false;
            });
        });
    }

    function initAutoSubmit() {
        document.querySelectorAll('[data-ot-autosubmit]').forEach(function (form) {
            form.querySelectorAll('[data-ot-autosubmit-field]').forEach(function (field) {
                field.addEventListener('change', function () {
                    form.submit();
                });
            });
        });
    }

    function initHeaderShadow() {
        var header = document.querySelector('.ot-header');
        if (!header) {
            return;
        }

        function update() {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        }

        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTabs();
        initShare();
        initDropdowns();
        initAutoSubmit();
        initHeaderShadow();
    });
})();
