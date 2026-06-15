@extends('layouts.admin')

@section('title', 'Frontend Theme')

@section('content')
<h1 style="margin-bottom:.5rem;">Frontend Theme</h1>
<p style="color:#64748b;margin-bottom:2rem;">Choose a visual theme for the public site. Changes apply immediately to all visitors.</p>

<form action="{{ route('admin.themes.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="theme-picker-grid">
        @foreach($themes as $key => $theme)
            <label class="theme-picker-card @if($currentTheme === $key) is-active @endif">
                <input type="radio" name="theme" value="{{ $key }}" @checked($currentTheme === $key)>
                <div class="theme-picker-preview">
                    @foreach($theme['preview'] as $color)
                        <span style="background:{{ $color }}"></span>
                    @endforeach
                </div>
                <strong>{{ $theme['name'] }}</strong>
                <p>{{ $theme['description'] }}</p>
                @if($currentTheme === $key)
                    <span class="theme-picker-badge">Active</span>
                @endif
            </label>
        @endforeach
    </div>

    @error('theme')
        <p class="field-error" style="margin-top:1rem;">{{ $message }}</p>
    @enderror

    <div style="margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary">Save Theme</button>
        <a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener">Preview site →</a>
    </div>
</form>
@endsection

@push('styles')
<style>
.theme-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.25rem;
}
.theme-picker-card {
    position: relative;
    display: block;
    background: var(--card);
    border: 2px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s;
}
.theme-picker-card:hover {
    border-color: var(--primary);
}
.theme-picker-card.is-active {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(230,57,70,.15);
}
.theme-picker-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.theme-picker-preview {
    display: flex;
    gap: .5rem;
    margin-bottom: 1rem;
}
.theme-picker-preview span {
    flex: 1;
    height: 48px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,.08);
}
.theme-picker-card strong {
    display: block;
    margin-bottom: .35rem;
    font-size: 1.05rem;
}
.theme-picker-card p {
    font-size: .88rem;
    color: var(--muted);
    line-height: 1.45;
    margin: 0;
}
.theme-picker-badge {
    display: inline-block;
    margin-top: .75rem;
    background: var(--primary);
    color: #fff;
    font-size: .75rem;
    font-weight: 700;
    padding: .2rem .6rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.theme-picker-card').forEach(function (card) {
    card.addEventListener('click', function () {
        document.querySelectorAll('.theme-picker-card').forEach(function (c) {
            c.classList.remove('is-active');
            var badge = c.querySelector('.theme-picker-badge');
            if (badge) badge.remove();
        });
        card.classList.add('is-active');
        card.querySelector('input').checked = true;
        var badge = document.createElement('span');
        badge.className = 'theme-picker-badge';
        badge.textContent = 'Active';
        card.appendChild(badge);
    });
});
</script>
@endpush
