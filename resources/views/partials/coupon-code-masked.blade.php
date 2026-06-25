@php
    $parts = $coupon->maskedCodeParts();
@endphp
<span class="coupon-code-masked {{ $class ?? '' }}" data-masked-code>
    <span class="coupon-code-visible">{{ $parts['visible'] }}</span><span class="coupon-code-blur">{{ $parts['hidden'] }}</span>
</span>
