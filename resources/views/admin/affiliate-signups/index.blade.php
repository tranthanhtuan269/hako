@extends('layouts.admin')

@section('title', 'Affiliate Signups')

@section('content')
<div class="admin-page-header">
    <div>
        <h1>Affiliate Signups</h1>
        <p class="form-hint" style="margin:.35rem 0 0;">
            Cuộn xuống để tải thêm. Bấm <strong>Đăng ký</strong> để mở link và đánh dấu dự án đã đăng ký.
            @if(! empty($scanSite))
                <span>Scan site: <code>{{ $scanSite }}</code></span>
            @endif
        </p>
    </div>
    @if(($pagination['total'] ?? 0) > 0)
        <span class="badge badge-muted" id="affiliate-signup-total">{{ number_format($pagination['total']) }} dự án</span>
    @endif
</div>

@if(! $scanConfigured)
    <div class="alert alert-error">
        Chưa cấu hình Affiliate Signups API. Vào <a href="{{ route('admin.integrations.index') }}">Integrations</a> để thiết lập URL.
    </div>
@elseif($error)
    <div class="alert alert-error">{{ $error }}</div>
@endif

<form method="get" action="{{ route('admin.affiliate-signups.index') }}" class="admin-search-bar" id="affiliate-signup-search">
    <input type="search" name="q" value="{{ $search }}" placeholder="Tìm project, domain, signup link, category...">
    <button type="submit" class="btn btn-outline">Search</button>
    @if($search !== '')
        <a href="{{ route('admin.affiliate-signups.index') }}" class="btn btn-outline">Clear</a>
    @endif
</form>

@if($projects === [] && ! $error && $scanConfigured)
    <div class="import-card">
        <p style="margin:0;">
            @if($search !== '')
                Không tìm thấy dự án nào khớp từ khóa <strong>{{ $search }}</strong>.
            @else
                Chưa có dự án đăng ký nào trên Scan.
            @endif
        </p>
    </div>
@elseif($projects !== [])
    <div class="affiliate-signup-table-wrap"
        id="affiliate-signup-feed"
        data-feed-url="{{ $feedUrl }}"
        data-search="{{ $search }}"
        data-page="1"
        data-has-more="{{ ($pagination['page'] ?? 1) < ($pagination['total_pages'] ?? 1) ? '1' : '0' }}">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Domain</th>
                    <th>Signup Link</th>
                    <th>Category</th>
                    <th>Added / Registered</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="affiliate-signup-tbody">
                @include('admin.affiliate-signups._rows', ['projects' => $projects])
            </tbody>
        </table>
        <div id="affiliate-signup-loader" class="affiliate-signup-loader" hidden>
            <span class="affiliate-signup-spinner" aria-hidden="true"></span>
            Đang tải thêm...
        </div>
        <div id="affiliate-signup-end" class="affiliate-signup-end" @if(($pagination['page'] ?? 1) < ($pagination['total_pages'] ?? 1)) hidden @endif>
            Đã hiển thị tất cả dự án.
        </div>
        <div id="affiliate-signup-sentinel" class="affiliate-signup-sentinel" aria-hidden="true"></div>
    </div>
@endif
@endsection

@push('styles')
<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.admin-page-header h1 { margin: 0; }
.admin-search-bar {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1.25rem;
}
.admin-search-bar input[type="search"] {
    flex: 1 1 280px;
    min-width: 0;
    padding: .55rem .75rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
}
.badge-muted {
    display: inline-block;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #f3f4f6;
    color: #374151;
    font-size: .85rem;
    font-weight: 600;
}
.badge-success {
    display: inline-block;
    margin-left: .35rem;
    padding: .15rem .45rem;
    border-radius: 999px;
    background: #dcfce7;
    color: #166534;
    font-size: .7rem;
    font-weight: 600;
    vertical-align: middle;
}
.affiliate-signup-row.is-registered {
    background: #f8fafc;
}
.affiliate-signup-row.is-registered td {
    color: #6b7280;
}
.affiliate-signup-loader,
.affiliate-signup-end {
    text-align: center;
    padding: 1rem;
    color: #6b7280;
    font-size: .9rem;
}
.affiliate-signup-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    margin-right: .35rem;
    border: 2px solid #d1d5db;
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: affiliate-signup-spin .7s linear infinite;
    vertical-align: -.15rem;
}
.affiliate-signup-sentinel {
    height: 1px;
}
@keyframes affiliate-signup-spin {
    to { transform: rotate(360deg); }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin-affiliate-signups.js') }}?v={{ filemtime(public_path('js/admin-affiliate-signups.js')) }}"></script>
@endpush
