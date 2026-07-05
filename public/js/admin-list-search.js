(function () {
    const DEBOUNCE_MS = 350;

    document.querySelectorAll('[data-live-search]').forEach((form) => {
        const input = form.querySelector('[data-live-search-input]');
        const clearBtn = form.querySelector('[data-live-search-clear]');

        if (!input) {
            return;
        }

        let timer = null;
        let lastSubmitted = input.value;

        const syncClearButton = () => {
            if (!clearBtn) {
                return;
            }

            clearBtn.hidden = input.value.trim() === '';
        };

        const submitForm = () => {
            const value = input.value.trim();

            if (value === lastSubmitted) {
                return;
            }

            lastSubmitted = value;
            form.requestSubmit();
        };

        input.addEventListener('input', () => {
            syncClearButton();
            window.clearTimeout(timer);
            timer = window.setTimeout(submitForm, DEBOUNCE_MS);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                window.clearTimeout(timer);
                submitForm();
            }

            if (event.key === 'Escape' && input.value !== '') {
                event.preventDefault();
                input.value = '';
                syncClearButton();
                window.clearTimeout(timer);
                submitForm();
            }
        });

        clearBtn?.addEventListener('click', () => {
            const clearUrl = clearBtn.getAttribute('data-clear-url');

            if (clearUrl) {
                window.location.assign(clearUrl);

                return;
            }

            input.value = '';
            syncClearButton();
            window.clearTimeout(timer);
            lastSubmitted = '';
            form.requestSubmit();
        });

        syncClearButton();
    });
})();
