@extends('layouts.admin')

@section('title', 'Auto Import Affiliate (Excel)')

@section('content')
<div class="admin-page-header">
    <div>
        <h1 style="margin:0;">Auto Import Affiliate (Excel)</h1>
        <p class="form-hint" style="margin:.4rem 0 0;">
            Upload your signup Excel, review pending stores, then process automatically
            (detect logo + write content for new stores; append coupons for existing stores).
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
        <a href="{{ route('admin.affiliate-excel-import.template') }}" class="btn btn-outline">Download template</a>
        <a href="{{ route('member.import-affiliate.create') }}" class="btn btn-outline">Manual import →</a>
    </div>
</div>

<div class="import-card" style="margin-bottom:1.25rem;">
    <h2 style="margin-top:0;">Upload Excel</h2>
    <p class="form-hint" style="margin-bottom:1rem;">
        Supports multi-sheet workbooks (Kickbooster, Goaffpro, CJ…). Columns:
        <code>Tên Store</code>, <code>Link Web</code>, <code>Link affiliate</code>,
        <code>Mã Coupon</code>, <code>Ofer</code>, description.
        User/Pass columns are ignored. Rows for the same store are grouped by STT / name.
    </p>
    <form method="POST" action="{{ route('admin.affiliate-excel-import.upload') }}" enctype="multipart/form-data" class="excel-upload-form">
        @csrf
        <div class="form-group" style="margin-bottom:.75rem;">
            <label for="excel_file">Excel file (.xlsx)</label>
            <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            @error('excel_file')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Upload &amp; queue records</button>
    </form>
</div>

<div class="import-card" style="margin-bottom:1.25rem;">
    <div class="admin-page-header" style="margin-bottom:1rem;">
        <div>
            <h2 style="margin:0;">Pending records</h2>
            <p class="form-hint" style="margin:.35rem 0 0;">
                <span id="pending-count-label">{{ $pendingCount }}</span> waiting to process
            </p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button
                type="button"
                class="btn btn-primary"
                id="process-all-btn"
                data-process-url="{{ route('admin.affiliate-excel-import.process-next') }}"
                @disabled($pendingCount < 1)
            >Process pending</button>
            @if($pendingCount > 0)
                <form method="POST" action="{{ route('admin.affiliate-excel-import.clear-pending') }}" onsubmit="return confirm('Clear all pending records?');">
                    @csrf
                    <button type="submit" class="btn btn-outline">Clear pending</button>
                </form>
            @endif
        </div>
    </div>

    <div class="coupon-source-options" id="coupon-source-options" @if($pendingCount < 1) hidden @endif>
        <strong>Coupon source before process</strong>
        <label class="coupon-source-option">
            <input type="radio" name="coupon_source" value="excel_only" checked>
            <span>
                <strong>Excel only</strong>
                <small>Chỉ dùng các coupon trong file Excel đã upload.</small>
            </span>
        </label>
        <label class="coupon-source-option">
            <input type="radio" name="coupon_source" value="excel_and_detected">
            <span>
                <strong>Excel + detected</strong>
                <small>Dùng coupon Excel và thêm coupon hệ thống tự lấy từ Scan/detect (nếu có).</small>
            </span>
        </label>
    </div>

    @if($pendingItems->isEmpty())
        <p style="margin:0;color:#64748b;">No pending records. Upload an Excel file to get started.</p>
    @else
        <div class="table-scroll">
            <table class="admin-table" id="pending-table">
                <thead>
                    <tr>
                        <th>Sheet</th>
                        <th>Store</th>
                        <th>Affiliate</th>
                        <th>Offers</th>
                        <th>Source row</th>
                        <th class="table-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingItems as $item)
                        <tr data-item-id="{{ $item->id }}">
                            <td>
                                <span class="badge badge-muted">{{ $item->sheet_name ?: '—' }}</span>
                                @if($item->import)
                                    <div class="form-hint" style="margin:.25rem 0 0;">{{ $item->import->original_filename }}</div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $item->store_name }}</strong>
                                @if($item->website)
                                    <div class="form-hint" style="margin:.25rem 0 0;">
                                        <a href="{{ $item->website }}" target="_blank" rel="noopener">{{ $item->website }}</a>
                                    </div>
                                @endif
                                @if($item->category_name)
                                    <div class="form-hint">Category: {{ $item->category_name }}</div>
                                @endif
                            </td>
                            <td class="small">
                                <a href="{{ $item->affiliate_url }}" target="_blank" rel="noopener" class="affiliate-link-cell">{{ \Illuminate\Support\Str::limit($item->affiliate_url, 48) }}</a>
                            </td>
                            <td>
                                <strong>{{ count($item->offers ?? []) }}</strong>
                                <ul class="offer-preview-list">
                                    @foreach(array_slice($item->offers ?? [], 0, 3) as $offer)
                                        <li>
                                            {{ $offer['title'] ?? 'Offer' }}
                                            @if(!empty($offer['code']))
                                                <code>{{ $offer['code'] }}</code>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if(count($item->offers ?? []) > 3)
                                        <li class="form-hint">+{{ count($item->offers) - 3 }} more</li>
                                    @endif
                                </ul>
                            </td>
                            <td>{{ $item->source_row ?: '—' }}</td>
                            <td>
                                <div style="display:flex;gap:.35rem;flex-wrap:wrap;">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-sm js-process-one"
                                        data-url="{{ route('admin.affiliate-excel-import.process-item', $item) }}"
                                    >Process</button>
                                    <form method="POST" action="{{ route('admin.affiliate-excel-import.destroy-item', $item) }}" onsubmit="return confirm('Remove this pending record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline btn-sm">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $pendingItems->links() }}
    @endif
</div>

@if($recentItems->isNotEmpty())
<div class="import-card">
    <h2 style="margin-top:0;">Recently processed</h2>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Store</th>
                    <th>Result</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentItems as $item)
                    <tr>
                        <td>
                            @if($item->status === 'done')
                                <span class="badge badge-success">Done</span>
                            @else
                                <span class="badge badge-danger">Failed</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $item->store_name }}</strong>
                            @if($item->store)
                                <div class="form-hint" style="margin:.25rem 0 0;">
                                    <a href="{{ route('admin.stores.edit', $item->store) }}">Edit store</a>
                                    ·
                                    <a href="{{ route('stores.show', $item->store->slug) }}" target="_blank" rel="noopener">View</a>
                                </div>
                            @endif
                        </td>
                        <td class="small">
                            @if($item->status === 'done')
                                {{ $item->was_existing_store ? 'Existing store' : 'New store' }}
                                — {{ $item->coupons_added }} coupon(s) added
                            @else
                                <div>{{ $item->error_message }}</div>
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm js-process-one"
                                    style="margin-top:.35rem;"
                                    data-url="{{ route('admin.affiliate-excel-import.process-item', $item) }}"
                                >Retry</button>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $item->processed_at?->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="excel-progress-modal" id="excel-progress-modal" hidden>
    <div class="excel-progress-modal__backdrop"></div>
    <div class="excel-progress-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="excel-progress-title">
        <div class="excel-progress-modal__header">
            <div>
                <h3 id="excel-progress-title">Processing store</h3>
                <p class="form-hint" id="excel-progress-subtitle" style="margin:.25rem 0 0;"></p>
            </div>
            <div class="excel-progress-batch" id="excel-progress-batch" hidden></div>
        </div>
        <div class="excel-progress-modal__body">
            <div class="excel-progress-store">
                <strong id="excel-progress-store-name">—</strong>
                <div class="form-hint" id="excel-progress-store-meta"></div>
            </div>
            <ol class="excel-progress-steps" id="excel-progress-steps"></ol>
            <div class="excel-progress-history" id="excel-progress-history" hidden>
                <strong>Completed stores</strong>
                <ul id="excel-progress-history-list"></ul>
            </div>
        </div>
        <div class="excel-progress-modal__footer">
            <p class="form-hint" id="excel-progress-footer-text" style="margin:0;flex:1;">Please wait — do not close this window.</p>
            <button type="button" class="btn btn-primary" id="excel-progress-close" hidden>Close</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.import-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem 1.35rem;
}
.badge {
    display: inline-block;
    padding: .2rem .5rem;
    border-radius: 4px;
    font-size: .75rem;
    font-weight: 600;
}
.badge-success { background: #dcfce7; color: #166534; }
.badge-muted { background: #f3f4f6; color: #6b7280; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.offer-preview-list {
    margin: .35rem 0 0;
    padding-left: 1.1rem;
    font-size: .82rem;
    color: #64748b;
}
.offer-preview-list code { font-size: .75rem; }
.affiliate-link-cell { word-break: break-all; }
.table-scroll { overflow-x: auto; }
.btn-sm { padding: .3rem .55rem; font-size: .82rem; }
.coupon-source-options {
    margin: 0 0 1rem;
    padding: .9rem 1rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: .65rem;
}
.coupon-source-options > strong {
    font-size: .92rem;
}
.coupon-source-option {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    margin: 0;
    cursor: pointer;
}
.coupon-source-option input {
    margin-top: .2rem;
}
.coupon-source-option span {
    display: flex;
    flex-direction: column;
    gap: .15rem;
}
.coupon-source-option small {
    color: #64748b;
    font-size: .82rem;
}

.excel-progress-modal[hidden] { display: none !important; }
.excel-progress-modal {
    position: fixed;
    inset: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.excel-progress-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, .55);
}
.excel-progress-modal__dialog {
    position: relative;
    width: min(560px, 100%);
    max-height: calc(100vh - 2rem);
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
    display: flex;
    flex-direction: column;
}
.excel-progress-modal__header,
.excel-progress-modal__footer {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.15rem;
    border-bottom: 1px solid var(--border);
}
.excel-progress-modal__footer {
    border-bottom: 0;
    border-top: 1px solid var(--border);
    align-items: center;
}
.excel-progress-modal__header h3 {
    margin: 0;
    font-size: 1.05rem;
}
.excel-progress-modal__body {
    padding: 1rem 1.15rem 1.15rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.excel-progress-batch {
    font-size: .82rem;
    font-weight: 600;
    color: #334155;
    background: #f1f5f9;
    border-radius: 999px;
    padding: .3rem .7rem;
    white-space: nowrap;
}
.excel-progress-store strong {
    font-size: 1.05rem;
}
.excel-progress-steps {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: .55rem;
}
.excel-progress-step {
    display: grid;
    grid-template-columns: 1.5rem 1fr;
    gap: .65rem;
    align-items: start;
    padding: .7rem .8rem;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #f8fafc;
}
.excel-progress-step.is-running {
    border-color: #93c5fd;
    background: #eff6ff;
}
.excel-progress-step.is-done {
    border-color: #86efac;
    background: #f0fdf4;
}
.excel-progress-step.is-failed {
    border-color: #fca5a5;
    background: #fef2f2;
}
.excel-progress-step.is-skipped {
    opacity: .72;
}
.excel-progress-step__icon {
    width: 1.35rem;
    height: 1.35rem;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .75rem;
    font-weight: 700;
    background: #e2e8f0;
    color: #475569;
}
.excel-progress-step.is-running .excel-progress-step__icon {
    background: #dbeafe;
    color: #1d4ed8;
}
.excel-progress-step.is-done .excel-progress-step__icon {
    background: #bbf7d0;
    color: #166534;
}
.excel-progress-step.is-failed .excel-progress-step__icon {
    background: #fecaca;
    color: #991b1b;
}
.excel-progress-step__label {
    font-weight: 600;
    color: #0f172a;
}
.excel-progress-step__detail {
    margin-top: .2rem;
    font-size: .82rem;
    color: #64748b;
}
.excel-progress-step.is-running .excel-progress-step__detail {
    color: #1d4ed8;
}
.excel-progress-history ul {
    margin: .5rem 0 0;
    padding-left: 1.1rem;
    color: #475569;
    font-size: .85rem;
}
.excel-progress-spin {
    width: .85rem;
    height: .85rem;
    border: 2px solid #93c5fd;
    border-top-color: #1d4ed8;
    border-radius: 50%;
    animation: excel-spin .7s linear infinite;
}
@keyframes excel-spin { to { transform: rotate(360deg); } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const processAllBtn = document.getElementById('process-all-btn');
    const pendingLabel = document.getElementById('pending-count-label');
    const modal = document.getElementById('excel-progress-modal');
    const storeNameEl = document.getElementById('excel-progress-store-name');
    const storeMetaEl = document.getElementById('excel-progress-store-meta');
    const stepsEl = document.getElementById('excel-progress-steps');
    const batchEl = document.getElementById('excel-progress-batch');
    const subtitleEl = document.getElementById('excel-progress-subtitle');
    const footerText = document.getElementById('excel-progress-footer-text');
    const closeBtn = document.getElementById('excel-progress-close');
    const historyWrap = document.getElementById('excel-progress-history');
    const historyList = document.getElementById('excel-progress-history-list');
    let running = false;
    let allowClose = false;

    function setBusy(busy) {
        running = busy;
        if (processAllBtn) {
            processAllBtn.disabled = busy || Number(pendingLabel?.textContent || 0) < 1;
        }
        document.querySelectorAll('.js-process-one').forEach((btn) => {
            btn.disabled = busy;
        });
    }

    function openModal() {
        if (!modal) return;
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        allowClose = false;
        closeBtn.hidden = true;
        footerText.textContent = 'Please wait — do not close this window.';
    }

    function enableClose(message) {
        allowClose = true;
        closeBtn.hidden = false;
        footerText.textContent = message || 'Processing finished.';
    }

    function closeModal() {
        if (!allowClose || !modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    closeBtn?.addEventListener('click', () => {
        closeModal();
        window.location.reload();
    });

    function iconFor(status) {
        if (status === 'running') return '<span class="excel-progress-spin" aria-hidden="true"></span>';
        if (status === 'done') return '✓';
        if (status === 'failed') return '!';
        if (status === 'skipped') return '–';
        return String('');
    }

    function renderSteps(steps) {
        if (!stepsEl) return;
        stepsEl.innerHTML = '';
        (steps || []).forEach((step, index) => {
            const li = document.createElement('li');
            li.className = 'excel-progress-step is-' + (step.status || 'pending');
            li.dataset.stepKey = step.key;
            const num = index + 1;
            const icon = step.status === 'pending' ? String(num) : iconFor(step.status);
            li.innerHTML =
                '<span class="excel-progress-step__icon">' + icon + '</span>' +
                '<div>' +
                    '<div class="excel-progress-step__label">' + escapeHtml(step.label || step.key) + '</div>' +
                    '<div class="excel-progress-step__detail">' + escapeHtml(step.detail || statusLabel(step.status)) + '</div>' +
                '</div>';
            stepsEl.appendChild(li);
        });
    }

    function statusLabel(status) {
        return ({
            pending: 'Waiting…',
            running: 'Running…',
            done: 'Done',
            failed: 'Failed',
            skipped: 'Skipped',
        })[status] || '';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showStore(session) {
        storeNameEl.textContent = session.store_name || 'Store';
        const bits = [];
        if (session.offer_count != null) bits.push(session.offer_count + ' offer(s)');
        if (session.website) bits.push(session.website);
        storeMetaEl.textContent = bits.join(' · ');
        subtitleEl.textContent = session.message || 'Running import steps…';
        renderSteps(session.steps || []);
    }

    function appendHistory(text, ok = true) {
        historyWrap.hidden = false;
        const li = document.createElement('li');
        li.style.color = ok ? '#166534' : '#991b1b';
        li.textContent = text;
        historyList.appendChild(li);
    }

    function selectedCouponOptions() {
        const source = document.querySelector('input[name="coupon_source"]:checked')?.value || 'excel_only';
        return {
            coupon_source: source,
            include_detected_coupons: source === 'excel_and_detected',
        };
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok && !data.message) {
            throw new Error('Request failed (' + res.status + ')');
        }
        return data;
    }

    async function runSteps(session) {
        let next = session.next_step;
        let stepUrl = session.process_step_url;
        let guard = 0;

        while (next && guard < 20) {
            guard += 1;
            const steps = (session.steps || []).map((s) =>
                s.key === next ? { ...s, status: 'running', detail: 'Running…' } : s
            );
            renderSteps(steps);

            const result = await postJson(stepUrl, { step: next });
            session.steps = result.steps || session.steps;
            renderSteps(session.steps);

            if (!result.ok) {
                throw new Error(result.message || 'Step failed');
            }

            if (typeof result.pending_remaining === 'number' && pendingLabel) {
                pendingLabel.textContent = String(result.pending_remaining);
            }

            if (result.item_done) {
                return result;
            }

            next = result.next_step;
        }

        return session;
    }

    async function processBatch(startUrl) {
        if (running) return;
        const couponOptions = selectedCouponOptions();
        setBusy(true);
        openModal();
        historyList.innerHTML = '';
        historyWrap.hidden = true;
        subtitleEl.textContent = couponOptions.include_detected_coupons
            ? 'Mode: Excel + detected coupons'
            : 'Mode: Excel coupons only';

        let processed = 0;
        let failed = 0;
        let guard = 0;

        try {
            while (guard < 500) {
                guard += 1;
                const session = await postJson(startUrl, couponOptions);

                if (session.done || !session.ok || !session.next_step) {
                    if (session.done || session.pending_remaining === 0) {
                        break;
                    }
                    if (!session.ok) {
                        failed += 1;
                        appendHistory((session.store_name || 'Store') + ': ' + (session.message || 'Failed'), false);
                        continue;
                    }
                    break;
                }

                processed += 1;
                if (batchEl) {
                    batchEl.hidden = false;
                    batchEl.textContent = 'Store #' + processed + ' · pending ' + (session.pending_remaining ?? '—');
                }

                showStore(session);
                const result = await runSteps(session);
                appendHistory(
                    (result.store_name || session.store_name) + ': ' + (result.message || 'Done'),
                    true
                );

                if (result.batch_done || result.pending_remaining === 0) {
                    break;
                }
            }

            subtitleEl.textContent = 'Batch finished.';
            enableClose(`Done. Processed ${processed} store(s)` + (failed ? `, ${failed} failed` : '') + '.');
        } catch (err) {
            subtitleEl.textContent = 'Stopped with an error.';
            appendHistory(String(err.message || err), false);
            enableClose(String(err.message || err));
        } finally {
            setBusy(false);
        }
    }

    async function processOne(startUrl) {
        if (running) return;
        const couponOptions = selectedCouponOptions();
        setBusy(true);
        openModal();
        historyList.innerHTML = '';
        historyWrap.hidden = true;
        if (batchEl) batchEl.hidden = true;
        subtitleEl.textContent = couponOptions.include_detected_coupons
            ? 'Mode: Excel + detected coupons'
            : 'Mode: Excel coupons only';

        try {
            const session = await postJson(startUrl, couponOptions);
            if (!session.ok) {
                throw new Error(session.message || 'Could not start processing');
            }
            showStore(session);
            const result = await runSteps(session);
            appendHistory((result.store_name || session.store_name) + ': ' + (result.message || 'Done'), true);
            subtitleEl.textContent = 'Store finished.';
            enableClose(result.message || 'Done');
        } catch (err) {
            subtitleEl.textContent = 'Stopped with an error.';
            appendHistory(String(err.message || err), false);
            enableClose(String(err.message || err));
        } finally {
            setBusy(false);
        }
    }

    processAllBtn?.addEventListener('click', () => {
        processBatch(processAllBtn.dataset.processUrl);
    });

    document.querySelectorAll('.js-process-one').forEach((btn) => {
        btn.addEventListener('click', () => processOne(btn.dataset.url));
    });
})();
</script>
@endpush
