<div class="events-calendar">
    <div class="events-calendar__navigation">
        <button type="button" wire:click="previousMonth">{{ __('capell-events::navigation.previous_month') }}</button>
        <strong>{{ \Carbon\CarbonImmutable::create($year, $month, 1)->translatedFormat('F Y') }}</strong>
        <button type="button" wire:click="nextMonth">{{ __('capell-events::navigation.next_month') }}</button>
        <button type="button" wire:click="today">{{ __('capell-events::navigation.today') }}</button>
    </div>

    <div class="events-calendar__grid">
        @foreach ($days as $day)
            <button
                type="button"
                wire:click="selectDate('{{ $day->date->format('Y-m-d') }}')"
                class="events-calendar__day @if (! $day->isCurrentMonth) events-calendar__day--muted @endif @if ($day->isSelected) events-calendar__day--selected @endif"
            >
                <span>{{ $day->date->day }}</span>
                @if ($day->occurrenceCount > 0)
                    <small>{{ trans_choice('capell-events::messages.occurrence_count', $day->occurrenceCount, ['count' => $day->occurrenceCount]) }}</small>
                @endif
            </button>
        @endforeach
    </div>

    <div class="events-calendar__selected">
        @forelse ($selectedOccurrences as $occurrence)
            <article>
                <h3>{{ $occurrence->event->title ?? $occurrence->event->name }}</h3>
                <p>{{ $occurrence->starts_at->format('H:i') }}</p>
            </article>
        @empty
            <p>{{ __('capell-events::messages.no_events_found') }}</p>
        @endforelse
    </div>
</div>
