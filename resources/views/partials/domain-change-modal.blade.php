<div class="domain-change-modal" id="domain-change-modal" hidden aria-hidden="true">
    <div class="domain-change-modal-backdrop" data-domain-change-close></div>
    <div class="domain-change-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="domain-change-modal-title">
        <button type="button" class="domain-change-modal-close" data-domain-change-close aria-label="Close">&times;</button>
        <h2 id="domain-change-modal-title">Change Domain</h2>
        <p class="form-hint" style="margin-bottom:1rem;">
            Scans all blog posts, stores, and coupons, then replaces links that use the old domain with the new domain.
            This updates content fields only (titles, descriptions, HTML, affiliate URLs, etc.).
        </p>

        <form id="domain-change-form">
            @csrf
            <div class="form-group">
                <label for="domain_change_old">Old domain</label>
                <input
                    type="text"
                    id="domain_change_old"
                    name="old_domain"
                    value="{{ config('site.domain') }}"
                    required
                    maxlength="253"
                    placeholder="example.com"
                >
            </div>
            <div class="form-group">
                <label for="domain_change_new">New domain</label>
                <input
                    type="text"
                    id="domain_change_new"
                    name="new_domain"
                    required
                    maxlength="253"
                    placeholder="new-example.com"
                >
            </div>

            <p id="domain-change-error" class="form-error" hidden></p>
            <p id="domain-change-success" class="domain-change-success" hidden></p>

            <div class="domain-change-modal-actions">
                <button type="button" class="btn btn-outline" data-domain-change-close>Cancel</button>
                <button type="submit" class="btn btn-primary" id="domain-change-submit">Change domain</button>
            </div>
        </form>
    </div>
</div>

@once
    @push('styles')
    <style>
    .domain-change-modal {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .domain-change-modal[hidden] {
        display: none !important;
    }
    .domain-change-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, .55);
    }
    .domain-change-modal-dialog {
        position: relative;
        width: min(100%, 32rem);
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--border, #e2e8f0);
        box-shadow: 0 20px 45px rgba(15, 23, 42, .18);
        padding: 1.35rem 1.35rem 1.15rem;
    }
    .domain-change-modal-dialog h2 {
        margin: 0 0 .35rem;
        font-size: 1.2rem;
    }
    .domain-change-modal-close {
        position: absolute;
        top: .65rem;
        right: .75rem;
        border: 0;
        background: transparent;
        font-size: 1.5rem;
        line-height: 1;
        color: #64748b;
        cursor: pointer;
    }
    .domain-change-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: .65rem;
        margin-top: 1rem;
    }
    .domain-change-success {
        margin-top: .75rem;
        color: #047857;
        font-size: .92rem;
    }
    </style>
    @endpush

    @push('scripts')
    <script>
    (() => {
        const modal = document.getElementById('domain-change-modal');
        const form = document.getElementById('domain-change-form');
        const submitBtn = document.getElementById('domain-change-submit');
        const errorEl = document.getElementById('domain-change-error');
        const successEl = document.getElementById('domain-change-success');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        const endpoint = @json(route('admin.domain-change.store'));

        if (!modal || !form) {
            return;
        }

        function openModal() {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            errorEl.hidden = true;
            successEl.hidden = true;
            document.getElementById('domain_change_new')?.focus();
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Change domain';
        }

        document.querySelectorAll('[data-domain-change-open]').forEach((button) => {
            button.addEventListener('click', openModal);
        });

        modal.querySelectorAll('[data-domain-change-close]').forEach((element) => {
            element.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorEl.hidden = true;
            successEl.hidden = true;

            const oldDomain = document.getElementById('domain_change_old').value.trim();
            const newDomain = document.getElementById('domain_change_new').value.trim();

            if (!oldDomain || !newDomain) {
                errorEl.textContent = 'Please enter both old and new domain.';
                errorEl.hidden = false;
                return;
            }

            if (!window.confirm(`Replace all links from "${oldDomain}" to "${newDomain}" in blogs, stores, and coupons?`)) {
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Scanning…';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        old_domain: oldDomain,
                        new_domain: newDomain,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.ok) {
                    errorEl.textContent = data.message || 'Could not change domain.';
                    errorEl.hidden = false;
                    return;
                }

                const summary = data.summary;
                successEl.textContent =
                    `${data.message} `
                    + `Posts: ${summary.posts.updated}/${summary.posts.scanned} updated (${summary.posts.replacements} links). `
                    + `Stores: ${summary.stores.updated}/${summary.stores.scanned} updated (${summary.stores.replacements} links). `
                    + `Coupons: ${summary.coupons.updated}/${summary.coupons.scanned} updated (${summary.coupons.replacements} links).`;
                successEl.hidden = false;
            } catch (error) {
                errorEl.textContent = 'Network error while changing domain.';
                errorEl.hidden = false;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Change domain';
            }
        });
    })();
    </script>
    @endpush
@endonce
