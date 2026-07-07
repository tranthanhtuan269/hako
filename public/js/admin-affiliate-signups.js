document.addEventListener('DOMContentLoaded', function () {
    const feed = document.getElementById('affiliate-signup-feed');
    const tbody = document.getElementById('affiliate-signup-tbody');
    const loader = document.getElementById('affiliate-signup-loader');
    const endMarker = document.getElementById('affiliate-signup-end');
    const sentinel = document.getElementById('affiliate-signup-sentinel');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!feed || !tbody) {
        return;
    }

    let loading = false;
    let page = parseInt(feed.dataset.page || '1', 10);
    let hasMore = feed.dataset.hasMore === '1';

    function setLoading(state) {
        loading = state;

        if (loader) {
            loader.hidden = !state;
        }
    }

    function markEnd() {
        hasMore = false;
        feed.dataset.hasMore = '0';

        if (endMarker) {
            endMarker.hidden = false;
        }
    }

    async function loadMore() {
        if (!hasMore || loading) {
            return;
        }

        const nextPage = page + 1;
        setLoading(true);

        const url = new URL(feed.dataset.feedUrl, window.location.origin);
        url.searchParams.set('page', String(nextPage));

        const search = feed.dataset.search || '';
        if (search !== '') {
            url.searchParams.set('q', search);
        }

        try {
            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'Không tải được thêm dự án.');
            }

            if (data.html) {
                tbody.insertAdjacentHTML('beforeend', data.html);
            }

            page = data.page || nextPage;
            feed.dataset.page = String(page);

            if (!data.has_more) {
                markEnd();
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    }

    if (sentinel && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    loadMore();
                }
            });
        }, { rootMargin: '240px 0px' });

        observer.observe(sentinel);
    } else {
        window.addEventListener('scroll', function () {
            if (!hasMore || loading) {
                return;
            }

            const scrollBottom = window.innerHeight + window.scrollY;
            const threshold = document.body.offsetHeight - 200;

            if (scrollBottom >= threshold) {
                loadMore();
            }
        });
    }

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('.js-affiliate-signup-register');
        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();

        const row = button.closest('.affiliate-signup-row');
        const signupUrl = button.dataset.signupUrl || '';
        const registerUrl = button.dataset.registerUrl || '';

        button.disabled = true;

        try {
            if (registerUrl) {
                const response = await fetch(registerUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        signup_link: signupUrl,
                        project: button.dataset.project || '',
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.ok) {
                    throw new Error('Không lưu được trạng thái đăng ký.');
                }

                if (row) {
                    row.classList.add('is-registered');

                    const titleCell = row.querySelector('td:first-child');
                    if (titleCell && !titleCell.querySelector('.affiliate-signup-status')) {
                        titleCell.insertAdjacentHTML('beforeend', ' <span class="badge badge-success affiliate-signup-status">Đã đăng ký</span>');
                    }

                    const dateCell = row.querySelector('.affiliate-signup-date');
                    if (dateCell && data.registered_at) {
                        dateCell.textContent = data.registered_at;
                    }

                    button.replaceWith(createRegisteredButton());
                }
            }

            if (signupUrl) {
                window.open(signupUrl, '_blank', 'noopener,noreferrer');
            }
        } catch (error) {
            button.disabled = false;
            alert(error.message || 'Có lỗi khi đăng ký.');
        }
    });

    function createRegisteredButton() {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline btn-sm';
        btn.disabled = true;
        btn.textContent = 'Đã đăng ký';

        return btn;
    }
});
