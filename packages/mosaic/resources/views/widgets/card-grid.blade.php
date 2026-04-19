{{-- Card Grid Widget - Architectural Precision --}}
<section class="bg-mosaic-background py-mosaic-3xl px-mosaic-lg w-full">
    <div class="mx-auto max-w-7xl">
        {{-- Section label with coordinate marker --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-2xl uppercase tracking-widest"
        >
            [GRID: 002-A]
        </div>

        {{-- Cards Grid with ghost borders --}}
        <div class="{{ $this->getGridClass() }}">
            @forelse ($this->getCards() as $index => $card)
                <div
                    class="mosaic-card bg-mosaic-surface-container border-mosaic-outline-variant hover:bg-mosaic-surface-container-high border-t transition-colors"
                    style="border-radius: 0"
                >
                    {{-- Card number label --}}
                    <div
                        class="text-mosaic-primary text-mosaic-label-sm mb-mosaic-md font-bold uppercase tracking-widest"
                    >
                        [{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}]
                    </div>

                    {{-- Card image (if present) --}}
                    @if (isset($card['image']))
                        <div
                            class="mb-mosaic-lg h-48 w-full overflow-hidden"
                            style="
                                border-radius: 0;
                                border: 1px solid var(--mosaic-outline-variant);
                            "
                        >
                            <img
                                src="{{ $card['image'] }}"
                                alt="{{ $card['title'] ?? 'Card' }}"
                                class="h-full w-full object-cover"
                            />
                        </div>
                    @endif

                    {{-- Card title --}}
                    <h3
                        class="font-mosaic-headline text-mosaic-headline-sm text-mosaic-on-surface mb-mosaic-md font-bold"
                    >
                        {{ $card['title'] ?? 'Card Title' }}
                    </h3>

                    {{-- Card description --}}
                    @if (isset($card['description']))
                        <p
                            class="text-mosaic-on-surface-variant text-mosaic-body-md mb-mosaic-lg"
                        >
                            {{ $card['description'] }}
                        </p>
                    @endif

                    {{-- Card link (if present) --}}
                    @if (isset($card['link_text']) && isset($card['link_url']))
                        <a
                            href="{{ $card['link_url'] }}"
                            class="text-mosaic-primary text-mosaic-label-md hover:text-mosaic-primary-container font-bold uppercase tracking-wider transition-colors"
                        >
                            {{ $card['link_text'] }} →
                        </a>
                    @endif
                </div>
            @empty
                <div
                    class="py-mosaic-2xl text-mosaic-on-surface-variant col-span-full text-center"
                >
                    No cards to display
                </div>
            @endforelse
        </div>
    </div>
</section>
