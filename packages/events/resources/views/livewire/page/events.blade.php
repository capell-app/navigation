<div class="events-list">
    @forelse ($occurrenceGroups as $date => $occurrences)
        <section class="events-list__group">
            <h2>{{ \Carbon\CarbonImmutable::parse($date)->translatedFormat('j F Y') }}</h2>

            @foreach ($occurrences as $occurrence)
                <article class="events-list__item">
                    <h3>
                        @if ($occurrence->event->pageUrl?->exists)
                            <a href="{{ $occurrence->event->pageUrl->full_url }}">{{ $occurrence->event->title }}</a>
                        @else
                            {{ $occurrence->event->title ?? $occurrence->event->name }}
                        @endif
                    </h3>
                    <p>{{ $occurrence->starts_at->format('H:i') }}@if ($occurrence->ends_at) - {{ $occurrence->ends_at->format('H:i') }}@endif</p>
                    @if (filled($occurrence->location['name'] ?? null))
                        <p>{{ $occurrence->location['name'] }}</p>
                    @endif
                    @if (filled($occurrence->booking['url'] ?? null))
                        <a href="{{ $occurrence->booking['url'] }}">{{ $occurrence->booking['label'] ?? __('capell-events::form.booking') }}</a>
                    @endif
                </article>
            @endforeach
        </section>
    @empty
        <p class="no-results">{{ __('capell-events::messages.no_events_found') }}</p>
    @endforelse
</div>
