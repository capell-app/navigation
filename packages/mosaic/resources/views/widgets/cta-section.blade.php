{{-- CTA Section Widget - Architectural Precision --}}
<section class="bg-mosaic-surface py-mosaic-3xl px-mosaic-lg w-full">
    {{-- Grid background --}}
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

    <div class="relative z-10 mx-auto max-w-3xl text-center">
        {{-- Coordinate marker --}}
        <div
            class="text-mosaic-on-surface-variant font-mosaic-mono text-mosaic-label-sm mb-mosaic-lg uppercase tracking-widest"
        >
            [CTA: 004-A]
        </div>

        {{-- Headline --}}
        <h2
            class="font-mosaic-headline text-mosaic-display-md text-mosaic-on-surface mb-mosaic-lg font-bold"
            style="letter-spacing: -0.04em"
        >
            {{ $this->getHeadline() }}
        </h2>

        {{-- Description --}}
        @if ($description = $this->getDescription())
            <p
                class="text-mosaic-on-surface-variant text-mosaic-body-lg mb-mosaic-2xl mx-auto max-w-2xl"
            >
                {{ $description }}
            </p>
        @endif

        {{-- Buttons --}}
        <div class="gap-mosaic-md flex flex-col justify-center sm:flex-row">
            <a
                href="{{ $this->getPrimaryButtonUrl() }}"
                class="mosaic-btn mosaic-btn-primary font-bold"
                style="border-radius: 0"
            >
                {{ $this->getPrimaryButtonText() }}
            </a>

            @if ($this->hasSecondaryButton())
                <a
                    href="{{ $this->getSecondaryButtonUrl() }}"
                    class="mosaic-btn mosaic-btn-secondary font-bold"
                    style="border-radius: 0"
                >
                    {{ $this->getSecondaryButtonText() }}
                </a>
            @endif
        </div>
    </div>

    {{-- Accent lines --}}
    <div
        class="left-mosaic-lg right-mosaic-lg via-mosaic-primary absolute top-0 h-px bg-gradient-to-r from-transparent to-transparent"
    ></div>
    <div
        class="left-mosaic-lg right-mosaic-lg via-mosaic-primary absolute bottom-0 h-px bg-gradient-to-r from-transparent to-transparent"
    ></div>
</section>
