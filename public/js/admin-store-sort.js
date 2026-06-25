(function () {
    const tbody = document.getElementById('store-sortable');

    if (!tbody) {
        return;
    }

    const reorderUrl = tbody.dataset.reorderUrl;
    const sortEnabled = tbody.dataset.sortEnabled === '1';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let dragRow = null;
    let statusEl = null;

    function ensureStatusEl() {
        if (statusEl) {
            return statusEl;
        }

        statusEl = document.createElement('p');
        statusEl.className = 'coupon-sort-status';
        statusEl.setAttribute('aria-live', 'polite');
        tbody.closest('.coupon-sort-table-wrap')?.prepend(statusEl);

        return statusEl;
    }

    function setStatus(message, type) {
        const el = ensureStatusEl();
        el.textContent = message;
        el.dataset.type = type || '';
    }

    function currentOrder() {
        return Array.from(tbody.querySelectorAll('tr[data-store-id]')).map(function (row) {
            return parseInt(row.dataset.storeId, 10);
        });
    }

    function refreshOrderLabels() {
        const rows = Array.from(tbody.querySelectorAll('tr[data-store-id]'));
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

        if (!order.length) {
            return;
        }

        setStatus('Saving order…', 'pending');

        fetch(reorderUrl, {
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

    tbody.querySelectorAll('tr[data-store-id]').forEach(function (row) {
        const handle = row.querySelector('.coupon-sort-handle');

        if (!handle || !sortEnabled || handle.classList.contains('coupon-sort-handle--disabled')) {
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
                event.dataTransfer.setData('text/plain', row.dataset.storeId || '');
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
    });

    if (sortEnabled) {
        refreshOrderLabels();
    }
})();
