@if(!empty($siteSocialLinks))
    <div class="site-social-links{{ !empty($compact) ? ' site-social-links--compact' : '' }}" role="navigation" aria-label="Social media">
        @foreach($siteSocialLinks as $link)
            <a href="{{ $link['url'] }}"
                class="site-social-link site-social-link--{{ $link['key'] }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $link['label'] }}">
                @include('partials.site-social-icon', ['network' => $link['key']])
            </a>
        @endforeach
    </div>
@endif
