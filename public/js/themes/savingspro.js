document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('sp-coupon-modal');
    if (!modal) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const titleEl = document.getElementById('sp-modal-title');
    const subtitleEl = document.getElementById('sp-modal-subtitle');
    const codeEl = document.getElementById('sp-modal-code');
    const codeInlineEl = document.getElementById('sp-modal-code-inline');
    const expiresEl = document.getElementById('sp-modal-expires');
    const shopEl = document.getElementById('sp-modal-shop');
    const copyBtn = document.getElementById('sp-modal-copy');
    let activeCode = '';

    function openModal(data) {
        activeCode = data.code || '';
        titleEl.textContent = data.title || data.discount || 'Special Offer';
        subtitleEl.textContent = data.store
            ? 'Valid at ' + data.store + (data.discount ? ' — ' + data.discount : '')
            : 'Paste this code at checkout to save.';
        codeEl.textContent = activeCode || '—';
        codeInlineEl.textContent = activeCode || '';
        expiresEl.textContent = data.expires || '';
        shopEl.href = data.shopUrl || '#';
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sp-modal-open');
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sp-modal-open');
    }

    modal.querySelectorAll('[data-sp-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
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

    async function resolveCode(btn) {
        if (typeof window.resolveCouponCode === 'function') {
            const code = await window.resolveCouponCode(btn);
            if (code) {
                return { code: code, affiliateUrl: btn.dataset.affiliateUrl || '' };
            }
        }

        const revealUrl = btn.dataset.revealUrl;
        if (!revealUrl || !csrf) {
            return null;
        }

        const res = await fetch(revealUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();

        return {
            code: data.code || null,
            affiliateUrl: data.affiliate_url || btn.dataset.affiliateUrl || '',
        };
    }

    function openAffiliateTab(url) {
        if (!url) {
            return;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    }

    document.querySelectorAll('.sp-code-copy').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            try {
                const result = await resolveCode(btn);
                if (!result?.code) {
                    alert('This offer has no promo code. Click "Get Deal" for details.');
                    return;
                }

                if (btn.dataset.openAffiliateOnCopy === '1') {
                    openAffiliateTab(result.affiliateUrl);
                }

                if (typeof window.revealCouponCode === 'function') {
                    window.revealCouponCode(btn, result.code);
                }

                openModal({
                    code: result.code,
                    title: btn.dataset.couponTitle || '',
                    discount: btn.dataset.couponDiscount || '',
                    store: btn.dataset.couponStore || '',
                    expires: btn.dataset.couponExpires || '',
                    shopUrl: btn.dataset.shopUrl || '#',
                });

                await copyText(result.code, btn);
            } catch (e) {
                alert('Could not retrieve the code. Please try again.');
            }
        });
    });

    copyBtn.addEventListener('click', function () {
        if (activeCode) copyText(activeCode, copyBtn);
    });
});
