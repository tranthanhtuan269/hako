@php
    $statsDate = $statsDate ?? now()->toDateString();
    $period = $period ?? null;
    $action = $action ?? url()->current();
    $preserve = $preserve ?? [];
@endphp
<form method="GET" action="{{ $action }}" class="click-stats-date-filter">
    @foreach($preserve as $name => $value)
        @if(filled($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <label class="click-stats-date-filter__field">
        <span class="click-stats-date-filter__label">Click stats date</span>
        <input type="date" name="date" value="{{ $statsDate }}" required>
    </label>
    <button type="submit" class="btn btn-outline">View day</button>
    @if(request()->filled('date') && request('date') !== now()->toDateString())
        <a href="{{ $action }}?{{ http_build_query(array_filter(array_merge($preserve, ['date' => now()->toDateString()]))) }}" class="btn btn-outline">Today</a>
    @endif
    @if($period)
        <p class="click-stats-date-filter__hint form-hint">
            Showing clicks for
            <strong>{{ \Carbon\Carbon::parse($period->day)->format('M j, Y') }}</strong>
            · month {{ \Carbon\Carbon::parse($period->monthStart)->format('M Y') }}
            · year {{ \Carbon\Carbon::parse($period->yearStart)->format('Y') }}
        </p>
    @endif
</form>

@once
    @push('styles')
    <style>
    .click-stats-date-filter {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem 1rem;
        align-items: flex-end;
        margin-bottom: 1rem;
        padding: .85rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: .5rem;
    }
    .click-stats-date-filter__field {
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }
    .click-stats-date-filter__label {
        font-size: .8rem;
        font-weight: 600;
        color: var(--muted, #64748b);
    }
    .click-stats-date-filter input[type="date"] {
        padding: .45rem .6rem;
        border: 1px solid #cbd5e1;
        border-radius: .35rem;
        background: #fff;
        min-width: 11rem;
    }
    .click-stats-date-filter__hint {
        flex: 1 1 100%;
        margin: 0;
    }
    </style>
    @endpush
@endonce
