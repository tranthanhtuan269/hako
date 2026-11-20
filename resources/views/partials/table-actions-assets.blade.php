@once
    @push('styles')
        <style>
            .table-actions-col { width: 12rem; text-align: right; }
            .table-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: .25rem;
            }
            .table-action-form { display: inline; margin: 0; }
            .table-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2rem;
                height: 2rem;
                padding: 0;
                border: none;
                border-radius: 6px;
                background: transparent;
                color: #475569;
                cursor: pointer;
                text-decoration: none;
                transition: background .15s, color .15s, opacity .15s;
            }
            .table-action-btn:hover:not(.is-disabled):not(:disabled) {
                background: #f1f5f9;
                color: #0f172a;
            }
            .table-action-btn.is-disabled {
                opacity: .35;
                cursor: not-allowed;
            }
            .table-action-btn-danger:hover {
                background: #fef2f2;
                color: #dc2626;
            }
            .table-action-btn.is-copied {
                color: #16a34a;
            }
            .table-action-btn.js-toggle-ads:not(.is-ads-on) {
                color: #0f172a;
                opacity: 1;
            }
            .table-action-btn.js-toggle-ads.is-ads-on {
                color: #dc2626;
                background: #fef2f2;
                opacity: 1;
            }
            .table-action-btn.js-toggle-ads.is-ads-on svg {
                fill: currentColor;
            }
            .table-action-btn.js-toggle-ads:hover:not(:disabled) {
                opacity: 1;
            }
            .table-action-btn.js-toggle-ads:not(.is-ads-on):hover:not(:disabled) {
                color: #020617;
                background: #f1f5f9;
            }
            .table-action-btn.js-toggle-ads.is-ads-on:hover:not(:disabled) {
                color: #b91c1c;
                background: #fee2e2;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-copy-link');
                if (!btn) return;

                var url = btn.getAttribute('data-copy-url');
                if (!url) return;

                var defaultTitle = btn.getAttribute('data-copy-title') || 'Copy link';

                function markCopied() {
                    btn.classList.add('is-copied');
                    btn.setAttribute('title', 'Copied!');
                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        btn.setAttribute('title', defaultTitle);
                    }, 2000);
                }

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(markCopied).catch(function () {
                        window.prompt('Copy this link:', url);
                    });
                } else {
                    window.prompt('Copy this link:', url);
                    markCopied();
                }
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-toggle-ads');
                if (!btn || btn.disabled) {
                    return;
                }

                var url = btn.getAttribute('data-toggle-url');
                if (!url) {
                    return;
                }

                var csrf = document.querySelector('meta[name="csrf-token"]');
                btn.disabled = true;

                fetch(url, {
                    method: 'PATCH',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrf ? csrf.content : '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }).then(function (res) {
                    if (!res.ok) {
                        throw new Error('toggle-failed');
                    }

                    return res.json();
                }).then(function (data) {
                    var on = !!data.is_listed_ads;
                    var title = on
                        ? 'Listed on ads — click to mark not listed'
                        : 'Not listed on ads — click to mark listed';

                    btn.classList.toggle('is-ads-on', on);
                    btn.setAttribute('title', title);
                    btn.setAttribute('aria-label', title);
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                }).catch(function () {
                    btn.setAttribute('title', 'Could not update ads status');
                }).finally(function () {
                    btn.disabled = false;
                });
            });
        </script>
    @endpush
@endonce
