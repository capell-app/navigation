{{-- Image Gallery Widget - Architectural Precision --}}
<section class="bg-mosaic-background py-mosaic-3xl px-mosaic-lg w-full">
    <div class="mx-auto max-w-7xl">
        {{-- Section label --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-2xl uppercase tracking-widest"
        >
            [GALLERY: 005-A]
        </div>

        @if ($this->isCarousel())
            {{-- Carousel Layout --}}
            <div class="relative">
                <div class="swiper gallery-carousel">
                    <div class="swiper-wrapper">
                        @forelse ($this->getImages() as $image)
                            <div class="swiper-slide">
                                <div
                                    class="border-mosaic-outline-variant aspect-video overflow-hidden border"
                                    style="border-radius: 0"
                                >
                                    <img
                                        src="{{ $image['url'] ?? $image }}"
                                        alt="{{ $image['alt'] ?? 'Gallery image' }}"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                            </div>
                        @empty
                            <div
                                class="py-mosaic-3xl text-mosaic-on-surface-variant text-center"
                            >
                                No images to display
                            </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination mt-mosaic-lg"></div>
                </div>
            </div>
        @else
            {{-- Grid Layout --}}
            <div class="{{ $this->getGridClass() }}">
                @forelse ($this->getImages() as $index => $image)
                    <div
                        class="border-mosaic-outline-variant hover:border-mosaic-primary group cursor-pointer overflow-hidden border transition-colors"
                        style="border-radius: 0"
                    >
                        <img
                            src="{{ $image['url'] ?? $image }}"
                            alt="{{ $image['alt'] ?? 'Gallery image ' . ($index + 1) }}"
                            class="duration-mosaic-base h-64 w-full object-cover transition-transform group-hover:scale-105"
                        />

                        {{-- Coordinate marker on hover --}}
                        <div
                            class="p-mosaic-lg absolute inset-0 flex items-end bg-gradient-to-t from-black/50 to-transparent opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <span
                                class="text-mosaic-primary text-mosaic-label-sm font-bold uppercase tracking-wider"
                            >
                                [{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}]
                            </span>
                        </div>
                    </div>
                @empty
                    <div
                        class="py-mosaic-3xl text-mosaic-on-surface-variant col-span-full text-center"
                    >
                        No images to display
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</section>

@push('scripts')
    @once
        <link
            rel="stylesheet"
            href="https://unpkg.com/swiper/swiper-bundle.min.css"
        />
        <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    @endonce
@endpush
