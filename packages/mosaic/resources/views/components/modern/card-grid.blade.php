<?php

declare(strict_types=1);

?>

@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'columns' => (int) ($widget->getMeta('columns', 3)),
    'cards' => $widget->getMeta('cards', []),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

@php
    $gridCols = match ($columns) {
        2 => 'grid-cols-2',
        4 => 'grid-cols-4',
        default => 'grid-cols-3',
    };
@endphp

<x-capell-mosaic::widget.wrapper
    class="widget-ap-card-grid"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="padding: 3rem 2rem; background-color: var(--mosaic-surface)"
    >
        @if ($title || $content)
            <div style="margin-bottom: 2.5rem; max-width: 38rem">
                @if ($title)
                    <h2
                        class="ap-card-grid-headline"
                        style="
                            color: var(--mosaic-on-surface);
                            font-family: var(--mosaic-font-headline);
                            font-size: var(--mosaic-text-headline-lg);
                            font-weight: 700;
                            margin-bottom: 0.75rem;
                        "
                    >
                        {{ $title }}
                    </h2>
                @endif

                @if ($content)
                    <p
                        class="ap-card-grid-description"
                        style="
                            color: var(--mosaic-on-surface-variant);
                            font-size: var(--mosaic-text-body-lg);
                            line-height: 1.6;
                        "
                    >
                        {!! strip_tags($content) !!}
                    </p>
                @endif
            </div>
        @endif

        <div
            style="
                display: grid;
                grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));
                gap: 1.5rem;
            "
        >
            @forelse ($cards as $card)
                <div
                    class="ap-card mosaic-card"
                    style="background-color: var(--mosaic-surface-container)"
                >
                    @if (! empty($card['icon']))
                        <div style="font-size: 2rem; margin-bottom: 1rem">
                            {{ $card['icon'] }}
                        </div>
                    @endif

                    @if (! empty($card['title']))
                        <h3
                            class="ap-card-title"
                            style="
                                color: var(--mosaic-on-surface);
                                font-size: var(--mosaic-text-title-lg);
                                font-weight: 600;
                                margin-bottom: 0.5rem;
                            "
                        >
                            {{ $card['title'] }}
                        </h3>
                    @endif

                    @if (! empty($card['description']))
                        <p
                            class="ap-card-description"
                            style="
                                color: var(--mosaic-on-surface-variant);
                                font-size: var(--mosaic-text-body-md);
                                line-height: 1.55;
                            "
                        >
                            {{ $card['description'] }}
                        </p>
                    @endif

                    @if (! empty($card['link_text']) && ! empty($card['link_url']))
                        <a
                            href="{{ $card['link_url'] }}"
                            class="ap-card-link"
                            style="
                                display: inline-flex;
                                align-items: center;
                                gap: 0.375rem;
                                margin-top: 1rem;
                                font-size: var(--mosaic-text-body-sm);
                                font-weight: 600;
                                color: var(--mosaic-primary);
                                text-decoration: none;
                            "
                        >
                            {{ $card['link_text'] }} →
                        </a>
                    @endif
                </div>
            @empty
                <div
                    style="
                        grid-column: 1 / -1;
                        text-align: center;
                        padding: 3rem;
                        color: var(--mosaic-on-surface-variant);
                    "
                >
                    No cards configured.
                </div>
            @endforelse
        </div>
    </section>
</x-capell-mosaic::widget.wrapper>

<?php
