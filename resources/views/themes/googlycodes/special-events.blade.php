@php
    $events = [
        ['name' => 'Christmas', 'blurb' => 'Exclusive holiday deals and gifts', 'icon' => '🎁', 'tone' => 'red', 'q' => 'christmas'],
        ['name' => 'Labor Day', 'blurb' => 'End of summer savings', 'icon' => '🧳', 'tone' => 'blue', 'q' => 'labor day'],
        ['name' => 'Halloween', 'blurb' => 'Spooky specials and discounts', 'icon' => '🎃', 'tone' => 'orange', 'q' => 'halloween'],
        ['name' => "Father's Day", 'blurb' => 'Gifts for dad at special prices', 'icon' => '👤', 'tone' => 'navy', 'q' => "father's day"],
        ['name' => "Mother's Day", 'blurb' => 'Show mom love with great deals', 'icon' => '💗', 'tone' => 'pink', 'q' => "mother's day"],
        ['name' => 'Thanksgiving', 'blurb' => 'Pre-holiday shopping savings', 'icon' => '🦃', 'tone' => 'amber', 'q' => 'thanksgiving'],
        ['name' => 'Easter', 'blurb' => 'Spring deals and discounts', 'icon' => '🥚', 'tone' => 'green', 'q' => 'easter'],
        ['name' => 'New Year', 'blurb' => 'Start the year with amazing savings', 'icon' => '✨', 'tone' => 'cyan', 'q' => 'new year'],
        ['name' => 'Black Friday', 'blurb' => 'Biggest sales event of the year', 'icon' => '🛍️', 'tone' => 'slate', 'q' => 'black friday'],
        ['name' => 'Cyber Monday', 'blurb' => 'Online exclusive deals and discounts', 'icon' => '💻', 'tone' => 'indigo', 'q' => 'cyber monday'],
        ['name' => '11.11', 'blurb' => 'Global shopping festival', 'icon' => '🛒', 'tone' => 'crimson', 'q' => '11.11'],
    ];
@endphp

<section class="gc-events section" id="gc-special-events">
    <div class="container">
        <div class="gc-events-head">
            <div class="gc-events-intro">
                <h2 class="gc-events-title">
                    <span class="gc-events-cal" aria-hidden="true">📅</span>
                    Great Savings during Special Events
                </h2>
                <p>
                    Enjoy significant financial benefits during annual seasonal holidays. At {{ config('site.name') }},
                    we reveal enticing discounts and promotions during events such as:
                </p>
            </div>
            <button type="button" class="gc-events-toggle" data-gc-events-toggle aria-expanded="true" aria-controls="gc-events-grid">
                <span class="gc-events-caret" aria-hidden="true">⌃</span> Hide Events
            </button>
        </div>

        <div class="gc-events-grid" id="gc-events-grid">
            @foreach($events as $event)
                <article class="gc-event-card gc-event-card--{{ $event['tone'] }}">
                    <div class="gc-event-icon" aria-hidden="true">{{ $event['icon'] }}</div>
                    <h3>{{ $event['name'] }}</h3>
                    <p>{{ $event['blurb'] }}</p>
                    <a href="{{ route('search', ['q' => $event['q']]) }}">View deals →</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
