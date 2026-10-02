@extends('layouts.admin')

@section('title', 'Editor’s Picks')

@section('content')
<form action="{{ route('admin.editors-picks.update') }}" method="POST" id="picks-form">
    @csrf
    @method('PUT')

    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <div>
            <h1 style="margin:0 0 .35rem;">Editor’s Picks (Homepage)</h1>
            <p class="form-hint" style="margin:0;max-width:46rem;">
                Select which stores appear in the <strong>Editor’s Picks</strong> section on the homepage and arrange their display order.
            </p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
            <button type="submit" class="btn btn-primary" style="padding:.6rem 1.4rem;font-weight:600;">Save Changes</button>
            <a href="{{ route('admin.stores.index') }}" class="btn btn-outline">Manage Stores</a>
            <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">Preview Homepage →</a>
        </div>
    </div>

    <div class="picks-panel" style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(15,23,42,0.05);margin-bottom:1.5rem;">
        <h3 style="margin:0 0 .75rem;font-size:1.05rem;">+ Add Store to Editor’s Picks</h3>
        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
            <input type="text" id="store-search-filter" placeholder="Filter store by name..." style="min-width:200px;max-width:260px;padding:.55rem .75rem;border:1px solid var(--border);border-radius:8px;font:inherit;">
            <select id="select-store-to-add" style="flex:1;min-width:240px;max-width:420px;padding:.55rem .75rem;border:1px solid var(--border);border-radius:8px;font:inherit;">
                <option value="">-- Choose a store to add --</option>
                @foreach($availableStores as $st)
                    <option value="{{ $st->id }}"
                        data-name="{{ $st->name }}"
                        data-logo="{{ $st->logoUrl() }}"
                        data-initials="{{ $st->initials() }}"
                        data-deals="{{ $st->activeCouponsCount() }}"
                    >
                        {{ $st->name }} ({{ $st->activeCouponsCount() }} Deals)
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary" id="btn-add-store" style="padding:.55rem 1.25rem;">+ Add to Picks</button>
        </div>
    </div>

    <div class="picks-table-wrap" style="background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(15,23,42,0.05);">
        <table class="admin-table" style="width:100%;border-collapse:collapse;" id="picks-table">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid var(--border);text-align:left;">
                    <th style="width:3.5rem;text-align:center;padding:.75rem .5rem;">Order</th>
                    <th style="width:4.5rem;text-align:center;padding:.75rem .5rem;">Logo</th>
                    <th style="padding:.75rem 1rem;">Store Name</th>
                    <th style="width:8rem;text-align:center;padding:.75rem .5rem;">Active Deals</th>
                    <th style="width:8rem;text-align:center;padding:.75rem .5rem;">Reorder</th>
                    <th style="width:5rem;text-align:center;padding:.75rem .5rem;">Action</th>
                </tr>
            </thead>
            <tbody id="picks-tbody">
                @forelse($selectedStores as $index => $store)
                    <tr class="pick-row" data-store-id="{{ $store->id }}" style="border-bottom:1px solid var(--border);">
                        <td style="text-align:center;font-weight:700;color:#1d4ed8;">
                            <span class="pick-order-badge">#{{ $index + 1 }}</span>
                            <input type="hidden" name="store_ids[]" value="{{ $store->id }}">
                        </td>
                        <td style="text-align:center;padding:.5rem;">
                            @if($store->logoUrl())
                                <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" style="width:44px;height:44px;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:2px;background:#fff;" loading="lazy">
                            @else
                                <span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;background:#f1f5f9;font-weight:700;color:#475569;">{{ $store->initials() }}</span>
                            @endif
                        </td>
                        <td style="padding:.75rem 1rem;">
                            <strong style="font-size:.95rem;">{{ $store->name }}</strong>
                            <div style="font-size:.8rem;color:var(--muted);margin-top:2px;">/stores/{{ $store->slug }}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;font-weight:600;">
                                {{ number_format((int) ($store->active_coupons_count ?? $store->activeCouponsCount())) }} Deals
                            </span>
                        </td>
                        <td style="text-align:center;white-space:nowrap;">
                            <button type="button" class="btn btn-outline btn-sm btn-move-up" title="Move Up" style="padding:.25rem .5rem;font-size:.9rem;line-height:1;">↑</button>
                            <button type="button" class="btn btn-outline btn-sm btn-move-down" title="Move Down" style="padding:.25rem .5rem;font-size:.9rem;line-height:1;">↓</button>
                        </td>
                        <td style="text-align:center;">
                            <button type="button" class="btn btn-outline btn-sm btn-remove-pick" title="Remove from picks" style="color:#dc2626;border-color:#fca5a5;padding:.25rem .55rem;">🗑</button>
                        </td>
                    </tr>
                @empty
                    <tr id="picks-empty-row">
                        <td colspan="6" style="text-align:center;padding:2rem;color:var(--muted);">
                            No stores selected for Editor’s Picks yet. Choose a store from the dropdown above to add.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="padding:.65rem 1.75rem;font-size:.95rem;font-weight:600;">Save Editor’s Picks</button>
        <span class="form-hint" style="margin:0;">Stores are saved in the exact order shown above.</span>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.getElementById('picks-tbody');
    const select = document.getElementById('select-store-to-add');
    const filterInput = document.getElementById('store-search-filter');
    const addBtn = document.getElementById('btn-add-store');

    function updateOrderBadges() {
        const rows = tbody.querySelectorAll('.pick-row');
        rows.forEach((row, idx) => {
            const badge = row.querySelector('.pick-order-badge');
            if (badge) badge.textContent = '#' + (idx + 1);

            const upBtn = row.querySelector('.btn-move-up');
            const downBtn = row.querySelector('.btn-move-down');
            if (upBtn) {
                upBtn.disabled = (idx === 0);
                upBtn.style.opacity = (idx === 0) ? '0.35' : '1';
                upBtn.style.cursor = (idx === 0) ? 'not-allowed' : 'pointer';
            }
            if (downBtn) {
                downBtn.disabled = (idx === rows.length - 1);
                downBtn.style.opacity = (idx === rows.length - 1) ? '0.35' : '1';
                downBtn.style.cursor = (idx === rows.length - 1) ? 'not-allowed' : 'pointer';
            }
        });

        const emptyRow = document.getElementById('picks-empty-row');
        if (rows.length === 0) {
            if (!emptyRow) {
                const tr = document.createElement('tr');
                tr.id = 'picks-empty-row';
                tr.innerHTML = '<td colspan="6" style="text-align:center;padding:2rem;color:var(--muted);">No stores selected for Editor’s Picks yet. Choose a store from the dropdown above to add.</td>';
                tbody.appendChild(tr);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    }

    // Filter store dropdown
    if (filterInput && select) {
        filterInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            Array.from(select.options).forEach((opt, idx) => {
                if (idx === 0) return; // keep default placeholder
                const text = opt.text.toLowerCase();
                opt.style.display = text.includes(term) ? '' : 'none';
            });
        });
    }

    // Add store handler
    addBtn.addEventListener('click', function () {
        const selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            alert('Please choose a store from the dropdown.');
            return;
        }

        const storeId = selectedOption.value;
        const name = selectedOption.dataset.name || selectedOption.text;
        const logo = selectedOption.dataset.logo;
        const initials = selectedOption.dataset.initials || name.substring(0, 2).toUpperCase();
        const deals = selectedOption.dataset.deals || '0';

        const logoHtml = logo
            ? `<img src="${logo}" alt="${name}" style="width:44px;height:44px;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:2px;background:#fff;" loading="lazy">`
            : `<span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:8px;background:#f1f5f9;font-weight:700;color:#475569;">${initials}</span>`;

        const tr = document.createElement('tr');
        tr.className = 'pick-row';
        tr.dataset.storeId = storeId;
        tr.style.borderBottom = '1px solid var(--border)';
        tr.innerHTML = `
            <td style="text-align:center;font-weight:700;color:#1d4ed8;">
                <span class="pick-order-badge">#</span>
                <input type="hidden" name="store_ids[]" value="${storeId}">
            </td>
            <td style="text-align:center;padding:.5rem;">${logoHtml}</td>
            <td style="padding:.75rem 1rem;">
                <strong style="font-size:.95rem;">${name}</strong>
            </td>
            <td style="text-align:center;">
                <span class="badge" style="background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;font-weight:600;">
                    ${deals} Deals
                </span>
            </td>
            <td style="text-align:center;white-space:nowrap;">
                <button type="button" class="btn btn-outline btn-sm btn-move-up" title="Move Up" style="padding:.25rem .5rem;font-size:.9rem;line-height:1;">↑</button>
                <button type="button" class="btn btn-outline btn-sm btn-move-down" title="Move Down" style="padding:.25rem .5rem;font-size:.9rem;line-height:1;">↓</button>
            </td>
            <td style="text-align:center;">
                <button type="button" class="btn btn-outline btn-sm btn-remove-pick" title="Remove from picks" style="color:#dc2626;border-color:#fca5a5;padding:.25rem .55rem;">🗑</button>
            </td>
        `;

        const emptyRow = document.getElementById('picks-empty-row');
        if (emptyRow) emptyRow.remove();

        tbody.appendChild(tr);
        selectedOption.remove();
        select.value = '';
        if (filterInput) filterInput.value = '';
        Array.from(select.options).forEach(opt => opt.style.display = '');
        updateOrderBadges();
    });

    // Move up, move down, remove delegation
    tbody.addEventListener('click', function (e) {
        const upBtn = e.target.closest('.btn-move-up');
        const downBtn = e.target.closest('.btn-move-down');
        const removeBtn = e.target.closest('.btn-remove-pick');

        if (upBtn && !upBtn.disabled) {
            const row = upBtn.closest('.pick-row');
            const prev = row.previousElementSibling;
            if (prev && prev.classList.contains('pick-row')) {
                tbody.insertBefore(row, prev);
                updateOrderBadges();
            }
        } else if (downBtn && !downBtn.disabled) {
            const row = downBtn.closest('.pick-row');
            const next = row.nextElementSibling;
            if (next && next.classList.contains('pick-row')) {
                tbody.insertBefore(next, row);
                updateOrderBadges();
            }
        } else if (removeBtn) {
            const row = removeBtn.closest('.pick-row');
            const storeId = row.dataset.storeId;
            const nameEl = row.querySelector('strong');
            const name = nameEl ? nameEl.textContent.trim() : 'Store';

            // Add back to select
            const opt = document.createElement('option');
            opt.value = storeId;
            opt.textContent = name;
            opt.dataset.name = name;
            select.appendChild(opt);

            row.remove();
            updateOrderBadges();
        }
    });

    updateOrderBadges();
});
</script>
@endpush
