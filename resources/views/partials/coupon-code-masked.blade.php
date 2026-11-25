@php
    $parts = $coupon->maskedCodeParts();
    $showAll = request('coupon_popup') === '1' || request('show_codes') === '1';
@endphp
<span class="coupon-code-masked {{ $showAll ? 'is-revealed' : '' }} {{ $class ?? '' }}" data-masked-code data-full-code="{{ $coupon->code }}">
    @if($showAll && filled($coupon->code))
        {{ $coupon->code }}
    @else
        <span class="coupon-code-visible">{{ $parts['visible'] }}</span><span class="coupon-code-blur">{{ $parts['hidden'] }}</span>
    @endif
</span>
