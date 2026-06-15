@once
    @push('styles')
        <style>
            .table-actions-col { width: 10rem; text-align: right; }
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
                transition: background .15s, color .15s;
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
        </script>
    @endpush
@endonce
