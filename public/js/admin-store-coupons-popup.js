(function () {
    const modal = document.getElementById('store-coupons-modal');
    const titleEl = document.getElementById('store-coupons-title');
    const statusEl = document.getElementById('store-coupons-status');
    const loadingEl = document.getElementById('store-coupons-loading');
    const emptyEl = document.getElementById('store-coupons-empty');
    const tableWrap = document.getElementById('store-coupons-table-wrap');
    const tbody = document.getElementById('store-coupons-sortable');
    const createLink = document.getElementById('store-coupons-create');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!modal || !tbody) {
        return;
    }

    let sortUrl = '';
    let dragRow = null;
    let openTrigger = null;

    function setStatus(message, type) {
        if (!statusEl) {
            return;
        }

        if (!message) {
            statusEl.hidden = true;
            statusEl.textContent = '';
            statusEl.dataset.type = '';
            return;
        }

        statusEl.hidden = false;
        statusEl.textContent = message;
        statusEl.dataset.type = type || '';
    }

    function setViewState(state) {
        loadingEl.hidden = state !== 'loading';
        emptyEl.hidden = state !== 'empty';
        tableWrap.hidden = state !== 'table';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function currentOrder() {
        return Array.from(tbody.querySelectorAll('tr[data-coupon-id]')).map(function (row) {
            return parseInt(row.dataset.couponId, 10);
        });
    }

    function refreshOrderLabels() {
        const rows = Array.from(tbody.querySelectorAll('tr[data-coupon-id]'));
        const total = rows.length;

        rows.forEach(function (row, index) {
            const label = row.querySelector('[data-order-label]');

            if (label) {
                label.textContent = String(Math.max(1, total - index));
            }
        });
    }

    function saveOrder() {
        const order = currentOrder();

        if (!order.length || !sortUrl) {
            return;
        }

        setStatus('Saving order…', 'pending');

        fetch(sortUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            body: JSON.stringify({ order: order }),
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Save failed');
                }

                return response.json();
            })
            .then(function () {
                refreshOrderLabels();
                setStatus('Display order saved.', 'success');
            })
            .catch(function () {
                setStatus('Could not save order. Please try again.', 'error');
            });
    }

    function clearDropTargets() {
        tbody.querySelectorAll('tr.is-drop-target').forEach(function (row) {
            row.classList.remove('is-drop-target');
        });
    }

    function bindRowSort(row) {
        const handle = row.querySelector('.coupon-sort-handle');

        if (!handle) {
            return;
        }

        handle.addEventListener('mousedown', function () {
            row.draggable = true;
        });

        handle.addEventListener('mouseup', function () {
            row.draggable = false;
        });

        handle.addEventListener('mouseleave', function () {
            row.draggable = false;
        });

        row.addEventListener('dragstart', function (event) {
            dragRow = row;
            row.classList.add('is-dragging');

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', row.dataset.couponId || '');
            }
        });

        row.addEventListener('dragend', function () {
            row.draggable = false;
            row.classList.remove('is-dragging');
            clearDropTargets();
            dragRow = null;
            saveOrder();
        });

        row.addEventListener('dragover', function (event) {
            if (!dragRow || dragRow === row) {
                return;
            }

            event.preventDefault();

            if (event.dataTransfer) {
                event.dataTransfer.dropEffect = 'move';
            }

            clearDropTargets();
            row.classList.add('is-drop-target');

            const rect = row.getBoundingClientRect();
            const after = event.clientY > rect.top + rect.height / 2;

            if (after) {
                row.after(dragRow);
            } else {
                row.before(dragRow);
            }
        });

        row.addEventListener('dragleave', function () {
            row.classList.remove('is-drop-target');
        });

        row.addEventListener('drop', function (event) {
            event.preventDefault();
            clearDropTargets();
        });
    }

    function rowHtml(coupon) {
        return (
            '<tr data-coupon-id="' + coupon.id + '" data-update-url="' + escapeHtml(coupon.update_url) + '">' +
                '<td class="coupon-sort-col">' +
                    '<button type="button" class="coupon-sort-handle" aria-label="Drag to reorder" title="Drag to reorder">⠿</button>' +
                '</td>' +
                '<td><span class="coupon-sort-order" data-order-label>' + escapeHtml(coupon.store_sort_order) + '</span></td>' +
                '<td>' +
                    '<input type="text" class="store-coupons-input" name="title" value="' + escapeHtml(coupon.title) + '" required placeholder="Title">' +
                    '<textarea class="store-coupons-input store-coupons-input--desc" name="description" rows="2" placeholder="Description">' + escapeHtml(coupon.description || '') + '</textarea>' +
                '</td>' +
                '<td>' +
                    '<input type="text" class="store-coupons-input" name="code" value="' + escapeHtml(coupon.code || '') + '" placeholder="No code">' +
                '</td>' +
                '<td>' +
                    '<input type="datetime-local" class="store-coupons-input store-coupons-input--date" name="expires_at" value="' + escapeHtml(coupon.expires_at || '') + '">' +
                '</td>' +
                '<td class="store-coupons-flags">' +
                    '<label><input type="checkbox" name="is_active"' + (coupon.is_active ? ' checked' : '') + '> Active</label>' +
                    '<label><input type="checkbox" name="show_on_store"' + (coupon.show_on_store ? ' checked' : '') + '> On store</label>' +
                    '<label><input type="checkbox" name="is_featured"' + (coupon.is_featured ? ' checked' : '') + '> Featured</label>' +
                '</td>' +
                '<td class="store-coupons-row-actions">' +
                    '<button type="button" class="btn btn-primary btn-sm js-store-coupon-save">Save</button>' +
                    '<a class="btn btn-outline btn-sm" href="' + escapeHtml(coupon.edit_url) + '" target="_blank" rel="noopener">Full edit</a>' +
                '</td>' +
            '</tr>'
        );
    }

    function renderCoupons(coupons) {
        tbody.innerHTML = coupons.map(rowHtml).join('');
        tbody.querySelectorAll('tr[data-coupon-id]').forEach(bindRowSort);
        refreshOrderLabels();
    }

    function payloadFromRow(row) {
        return {
            title: row.querySelector('input[name="title"]').value.trim(),
            description: row.querySelector('textarea[name="description"]').value.trim() || null,
            code: row.querySelector('input[name="code"]').value.trim() || null,
            expires_at: row.querySelector('input[name="expires_at"]').value || null,
            is_active: row.querySelector('input[name="is_active"]').checked,
            show_on_store: row.querySelector('input[name="show_on_store"]').checked,
            is_featured: row.querySelector('input[name="is_featured"]').checked,
        };
    }

    function saveCouponRow(row) {
        const updateUrl = row.dataset.updateUrl;
        const payload = payloadFromRow(row);
        const button = row.querySelector('.js-store-coupon-save');

        if (!payload.title) {
            setStatus('Title is required.', 'error');
            row.querySelector('input[name="title"]')?.focus();
            return;
        }

        if (button) {
            button.disabled = true;
            button.textContent = 'Saving…';
        }

        setStatus('Saving coupon…', 'pending');

        fetch(updateUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
            },
            body: JSON.stringify(payload),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) {
                        const message = data?.message || Object.values(data?.errors || {})[0]?.[0] || 'Save failed';
                        throw new Error(message);
                    }

                    return data;
                });
            })
            .then(function () {
                setStatus('Coupon saved.', 'success');
            })
            .catch(function (error) {
                setStatus(error.message || 'Could not save coupon.', 'error');
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Save';
                }
            });
    }

    function openModal(trigger) {
        const url = trigger.getAttribute('data-coupons-url');
        const storeName = trigger.getAttribute('data-store-name') || 'Store';

        if (!url) {
            return;
        }

        openTrigger = trigger;
        titleEl.textContent = 'Coupons — ' + storeName;
        emptyEl.textContent = 'No coupons for this store yet.';
        setStatus('');
        setViewState('loading');
        tbody.innerHTML = '';
        modal.hidden = false;
        document.body.style.overflow = 'hidden';

        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Load failed');
                }

                return response.json();
            })
            .then(function (data) {
                sortUrl = data.sort_url || '';

                if (createLink) {
                    createLink.href = data.create_url || '#';
                }

                if (!data.coupons || !data.coupons.length) {
                    setViewState('empty');
                    return;
                }

                renderCoupons(data.coupons);
                setViewState('table');
            })
            .catch(function () {
                setViewState('empty');
                emptyEl.textContent = 'Could not load coupons. Please try again.';
                emptyEl.hidden = false;
                loadingEl.hidden = true;
            });
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
        dragRow = null;
        sortUrl = '';

        if (openTrigger) {
            openTrigger.focus();
            openTrigger = null;
        }
    }

    document.addEventListener('click', function (event) {
        const openBtn = event.target.closest('.js-store-coupons-open');

        if (openBtn) {
            event.preventDefault();
            openModal(openBtn);
            return;
        }

        if (event.target.closest('[data-store-coupons-close]')) {
            closeModal();
            return;
        }

        const saveBtn = event.target.closest('.js-store-coupon-save');

        if (saveBtn) {
            const row = saveBtn.closest('tr[data-coupon-id]');

            if (row) {
                saveCouponRow(row);
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });
})();
