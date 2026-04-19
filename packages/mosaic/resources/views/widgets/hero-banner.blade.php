{{-- Hero Banner Widget - Architectural Precision --}}
<section
    class="bg-mosaic-background py-mosaic-3xl relative w-full overflow-hidden"
>
    {{-- Grid background pattern --}}
    <div
        class="absolute inset-0 opacity-5"
        style="
            background-image:
                linear-gradient(rgba(77, 70, 53, 0.3) 1px, transparent 1px),
                linear-gradient(
                    90deg,
                    rgba(77, 70, 53, 0.3) 1px,
                    transparent 1px
                );
            background-size: 40px 40px;
        "
    ></div>

    <div class="px-mosaic-lg relative z-10 mx-auto max-w-7xl">
        {{-- Coordinate marker --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-2xl uppercase tracking-widest"
        >
            [REF: 001-A]
        </div>

        {{-- Main headline with tight tracking (Architectural Precision) --}}
        <div class="max-w-3xl">
            <h1
                class="font-mosaic-headline text-mosaic-display-lg text-mosaic-on-surface mb-mosaic-lg font-bold leading-tight"
                style="letter-spacing: -0.04em"
            >
                {{ $this->getTitle() }}
            </h1>

            @if ($subtitle = $this->getSubtitle())
                <p
                    class="text-mosaic-on-surface-variant text-mosaic-body-lg mb-mosaic-2xl"
                >
                    {{ $subtitle }}
                </p>
            @endif

            {{-- CTA Button - Gold gradient with zero-radius --}}
            <div class="gap-mosaic-md flex">
                <a
                    href="{{ $this->getCtaUrl() }}"
                    class="mosaic-btn mosaic-btn-primary font-bold"
                    style="border-radius: 0"
                >
                    {{ $this->getCtaText() }}
                </a>
            </div>
        </div>
    </div>

    {{-- Bottom accent line --}}
    <div
        class="left-mosaic-lg right-mosaic-lg via-mosaic-primary absolute bottom-0 h-px bg-gradient-to-r from-transparent to-transparent"
    ></div>
</section>
