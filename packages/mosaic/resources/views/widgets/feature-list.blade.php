{{-- Feature List Widget - Architectural Precision --}}
<section class="bg-mosaic-background py-mosaic-3xl px-mosaic-lg w-full">
    <div class="mx-auto max-w-4xl">
        {{-- Section label --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-2xl uppercase tracking-widest"
        >
            [FEATURES: 003-A]
        </div>

        {{-- Features Container - No dividers, uses tonal layering --}}
        <div class="space-y-mosaic-lg">
            @forelse ($this->getFeatures() as $index => $feature)
                <div
                    class="p-mosaic-lg bg-mosaic-surface-container border-mosaic-outline-variant hover:bg-mosaic-surface-container-high border-t transition-colors"
                    style="border-radius: 0"
                >
                    <div class="gap-mosaic-lg flex">
                        {{-- Icon (if present) --}}
                        @if (isset($feature['icon']))
                            <div
                                class="bg-mosaic-primary text-mosaic-on-primary-container flex h-12 w-12 flex-shrink-0 items-center justify-center font-bold"
                                style="border-radius: 0"
                            >
                                {{ $feature['icon'] }}
                            </div>
                        @endif

                        {{-- Content --}}
                        <div class="flex-grow">
                            {{-- Feature number --}}
                            <div
                                class="text-mosaic-primary text-mosaic-label-sm mb-mosaic-md font-bold uppercase tracking-widest"
                            >
                                [{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}]
                            </div>

                            {{-- Title --}}
                            <h3
                                class="font-mosaic-headline text-mosaic-headline-sm text-mosaic-on-surface mb-mosaic-md font-bold"
                            >
                                {{ $feature['title'] ?? 'Feature' }}
                            </h3>

                            {{-- Description --}}
                            @if (isset($feature['description']))
                                <p
                                    class="text-mosaic-on-surface-variant text-mosaic-body-md"
                                >
                                    {{ $feature['description'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div
                    class="py-mosaic-2xl text-mosaic-on-surface-variant text-center"
                >
                    No features to display
                </div>
            @endforelse
        </div>
    </div>
</section>
