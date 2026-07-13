@extends('layouts.admin')

@section('title', 'Change Domain')

@section('content')
<h1 style="margin-bottom:.5rem;">Change Domain</h1>
<p class="form-hint" style="margin-bottom:1.5rem;">
    Thay <strong>toàn bộ</strong> chỗ xuất hiện domain cũ (cả link lẫn text thường) trong
    <strong>blogs</strong>, <strong>stores</strong>, <strong>coupons</strong>,
    và tự động cập nhật <code>APP_URL</code>, <code>SITE_URL</code>, <code>SITE_DOMAIN</code> trong <code>.env</code>.
</p>

<div class="domain-change-note">
    <strong>Domain đang cấu hình</strong>
    <ul>
        <li><code>SITE_DOMAIN</code>: {{ $currentDomain }}</li>
        <li><code>SITE_URL</code>: {{ $currentSiteUrl }}</li>
        <li><code>APP_URL</code>: {{ $currentAppUrl }}</li>
    </ul>
</div>

<div class="domain-change-card">
    <form method="POST" action="{{ route('admin.domain-change.store') }}" id="domain-change-page-form">
        @csrf

        <div class="form-group">
            <label for="old_domain">Domain hiện tại (cần tìm)</label>
            <input
                type="text"
                id="old_domain"
                name="old_domain"
                value="{{ old('old_domain', $currentDomain) }}"
                required
                maxlength="253"
                placeholder="example.com"
            >
            <p class="form-hint">Tìm trong content (có hoặc không có https://).</p>
            @error('old_domain')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-group">
            <label for="new_domain">Domain mới</label>
            <input
                type="text"
                id="new_domain"
                name="new_domain"
                value="{{ old('new_domain') }}"
                required
                maxlength="253"
                placeholder="new-example.com"
            >
            @error('new_domain')<p class="form-error">{{ $message }}</p>@enderror
        </div>

        <div class="domain-change-actions">
            <button type="submit" class="btn btn-primary" id="domain-change-page-submit">Chuyển đổi</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Quay lại Dashboard</a>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
.domain-change-note {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    border-radius: 10px;
    padding: 1rem 1.15rem;
    margin-bottom: 1.25rem;
    max-width: 40rem;
}
.domain-change-note ul {
    margin: .5rem 0 0;
    padding-left: 1.2rem;
}
.domain-change-card {
    background: var(--card, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px;
    padding: 1.35rem 1.4rem;
    max-width: 40rem;
}
.domain-change-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
}
</style>
@endpush

@push('scripts')
<script>
(() => {
    const form = document.getElementById('domain-change-page-form');
    const submitBtn = document.getElementById('domain-change-page-submit');

    form?.addEventListener('submit', (event) => {
        const oldDomain = document.getElementById('old_domain').value.trim();
        const newDomain = document.getElementById('new_domain').value.trim();

        if (!oldDomain || !newDomain) {
            return;
        }

        if (!window.confirm(
            `Xác nhận thay mọi chỗ xuất hiện "${oldDomain}" bằng "${newDomain}" trong blogs, stores, coupons và cập nhật .env?`
        )) {
            event.preventDefault();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang quét & chuyển đổi…';
    });
})();
</script>
@endpush
