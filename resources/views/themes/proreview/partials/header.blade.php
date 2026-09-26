<header class="sticky top-0 z-40 bg-navy">
    <div class="container-site flex h-16 items-center gap-6 sm:h-[76px] xl:gap-9">
        <a class="inline-flex shrink-0 items-center no-underline" href="{{ route('home') }}">
            @if(!empty($siteLogoUrl))
                <img alt="{{ $siteDisplayName ?? config('site.name', 'ProReview') }}" class="h-[1.4rem] sm:h-[1.6rem] w-auto max-w-[180px] object-contain" src="{{ $siteLogoUrl }}">
            @elseif(file_exists(public_path('frontend/images/logo.png')))
                <img alt="{{ config('site.name', 'ProReview') }}" class="h-[1.4rem] w-auto" height="40" src="{{ asset('frontend/images/logo.png') }}" width="186">
            @else
                <span class="font-display text-xl font-bold tracking-tight text-white">{{ $siteDisplayName ?? config('site.name', 'ProReview') }}</span>
            @endif
        </a>
        <nav aria-label="Main" class="hidden items-center gap-0.5 xl:flex">
            <a class="rounded-full px-3.5 py-2.5 text-[15px] font-semibold whitespace-nowrap text-navy-text hover:text-white" href="{{ route('blog.index') }}">
                All Review
            </a>
            @php
                $headerCategories = \App\Models\Category::orderBy('sort_order')->take(3)->get();
            @endphp
            @foreach($headerCategories as $cat)
                <a class="rounded-full px-3.5 py-2.5 text-[15px] font-semibold whitespace-nowrap text-navy-text hover:text-white" href="{{ route('categories.show', $cat->slug) }}">
                    {{ $cat->name }}
                </a>
            @endforeach
            <a class="rounded-full px-3.5 py-2.5 text-[15px] font-semibold whitespace-nowrap text-navy-text hover:text-white" href="{{ route('stores.index') }}">
                All Stores
            </a>
            <div class="relative" data-dropdown="">
                <button aria-expanded="false" aria-haspopup="true" class="flex items-center gap-1.5 rounded-full px-3.5 py-2.5 text-[15px] font-semibold text-navy-text hover:text-white" type="button">
                    More
                    <svg aria-hidden="true" fill="none" height="14" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" viewBox="0 0 24 24" width="14">
                        <path d="m6 9 6 6 6-6"></path>
                    </svg>
                </button>
                <div class="absolute left-0 top-full z-50 mt-2 w-52 rounded-2xl border border-line bg-white p-2 shadow-float" data-dropdown-panel="" hidden="">
                    <a class="block rounded-lg px-3 py-2.5 text-[15px] font-semibold text-ink hover:bg-sand" href="{{ route('pages.about') }}">
                        About Us
                    </a>
                    <a class="block rounded-lg px-3 py-2.5 text-[15px] font-semibold text-ink hover:bg-sand" href="{{ route('pages.contact') }}">
                        Contact Us
                    </a>
                    <a class="block rounded-lg px-3 py-2.5 text-[15px] font-semibold text-ink hover:bg-sand" href="{{ route('pages.privacy') }}">
                        Privacy Policy
                    </a>
                    <a class="block rounded-lg px-3 py-2.5 text-[15px] font-semibold text-ink hover:bg-sand" href="{{ route('pages.terms') }}">
                        Terms of Use
                    </a>
                    @if(Route::has('pages.disclaimer'))
                        <a class="block rounded-lg px-3 py-2.5 text-[15px] font-semibold text-ink hover:bg-sand" href="{{ route('pages.disclaimer') }}">
                            Affiliate Disclaimer
                        </a>
                    @endif
                </div>
            </div>
            <a class="rounded-full px-3.5 py-2.5 text-[15px] font-semibold whitespace-nowrap text-navy-text hover:text-white" href="{{ route('pages.about') }}">
                About Us
            </a>
        </nav>
        <form action="{{ route('search') }}" method="GET" class="relative ml-auto hidden w-64 md:block xl:w-[300px]" role="search">
            <label class="sr-only" for="hdr-search">Search for a store or review</label>
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-navy-mute">
                <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="18">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
            </span>
            <input autocomplete="off" class="h-11 w-full rounded-full border border-navy-line bg-navy-3 pl-11 pr-4 text-sm font-medium text-white placeholder:text-navy-mute focus:border-accent focus:outline-none" id="hdr-search" name="q" placeholder="Search for a store or review" type="search" value="{{ request('q') }}">
        </form>
        <button aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu" class="ml-auto flex h-11 w-11 items-center justify-center rounded-full text-white hover:bg-navy-2 md:ml-0 xl:hidden" data-menu-toggle="" type="button">
            <svg aria-hidden="true" data-icon-open="" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-width="2.2" viewBox="0 0 24 24" width="24">
                <path d="M4 7h16M4 12h16M4 17h16"></path>
            </svg>
            <svg aria-hidden="true" class="hidden" data-icon-close="" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-width="2.2" viewBox="0 0 24 24" width="24">
                <path d="M6 6l12 12M18 6 6 18"></path>
            </svg>
        </button>
    </div>
    <div class="border-t border-navy-line bg-navy xl:hidden" hidden="" id="mobile-menu">
        <div class="container-site flex flex-col gap-4 py-4">
            <form action="{{ route('search') }}" method="GET" class="relative md:hidden" role="search">
                <label class="sr-only" for="mob-search">Search for a store or review</label>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-navy-mute">
                    <svg aria-hidden="true" fill="none" height="18" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="18">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </span>
                <input autocomplete="off" class="h-11 w-full rounded-full border border-navy-line bg-navy-3 pl-11 pr-4 text-sm font-medium text-white placeholder:text-navy-mute focus:border-accent focus:outline-none" id="mob-search" name="q" placeholder="Search for a store or review" type="search" value="{{ request('q') }}">
            </form>
            <ul class="flex flex-col gap-1">
                <li><a class="block rounded-xl px-4 py-3 text-base font-semibold text-navy-text hover:bg-navy-3 hover:text-white" href="{{ route('blog.index') }}">All Review</a></li>
                @foreach($headerCategories as $cat)
                    <li><a class="block rounded-xl px-4 py-3 text-base font-semibold text-navy-text hover:bg-navy-3 hover:text-white" href="{{ route('categories.show', $cat->slug) }}">{{ $cat->name }}</a></li>
                @endforeach
                <li><a class="block rounded-xl px-4 py-3 text-base font-semibold text-navy-text hover:bg-navy-3 hover:text-white" href="{{ route('stores.index') }}">All Stores</a></li>
                <li><a class="block rounded-xl px-4 py-3 text-base font-semibold text-navy-text hover:bg-navy-3 hover:text-white" href="{{ route('pages.about') }}">About Us</a></li>
                <li><a class="block rounded-xl px-4 py-3 text-base font-semibold text-navy-text hover:bg-navy-3 hover:text-white" href="{{ route('pages.contact') }}">Contact Us</a></li>
            </ul>
        </div>
    </div>
</header>
