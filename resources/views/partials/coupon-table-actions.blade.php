@php
    $publicUrl = route('coupons.show', $coupon->slug);
    $editUrl = $editUrl ?? route('member.coupons.edit', $coupon);
    $destroyUrl = $destroyUrl ?? route('member.coupons.destroy', $coupon);
    $showCopy = $showCopy ?? true;
    $deleteConfirm = $deleteConfirm ?? 'Delete this coupon?';
@endphp
<div class="table-actions">
    @if($coupon->is_active)
        <a href="{{ $publicUrl }}" class="table-action-btn" target="_blank" rel="noopener" title="View public page" aria-label="View public page">
            @include('partials.icons.eye')
        </a>
    @else
        <span class="table-action-btn is-disabled" title="Coupon is inactive" aria-label="Coupon is inactive">
            @include('partials.icons.eye')
        </span>
    @endif
    @if($showCopy)
        <button
            type="button"
            class="table-action-btn js-copy-link"
            data-copy-url="{{ $publicUrl }}"
            data-copy-title="Copy coupon link"
            title="Copy coupon link"
            aria-label="Copy coupon link"
        >
            @include('partials.icons.copy')
        </button>
    @endif
    <a href="{{ $editUrl }}" class="table-action-btn" title="Edit coupon" aria-label="Edit coupon">
        @include('partials.icons.edit')
    </a>
    <form action="{{ $destroyUrl }}" method="POST" class="table-action-form" onsubmit="return confirm(@json($deleteConfirm))">
        @csrf
        @method('DELETE')
        <button type="submit" class="table-action-btn table-action-btn-danger" title="Delete coupon" aria-label="Delete coupon">
            @include('partials.icons.trash')
        </button>
    </form>
</div>
