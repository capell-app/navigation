<?php

declare(strict_types=1);

?>

@props([
    'title' => $widget->translation?->title,
    'content' => $widget->translation?->content,
    'submitButtonText' => $widget->getMeta('submit_button_text', 'Submit'),
    'formFields' => $widget->getMeta('form_fields', []),
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])

<x-capell-mosaic::widget.wrapper
    class="widget-ap-form-section"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    <section
        style="padding: 3rem 2rem; background-color: var(--mosaic-surface)"
    >
        <div style="max-width: 32rem">
            @if ($title)
                <h2
                    class="ap-form-headline"
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
                    class="ap-form-description"
                    style="
                        color: var(--mosaic-on-surface-variant);
                        font-size: var(--mosaic-text-body-lg);
                        line-height: 1.6;
                        margin-bottom: 1.75rem;
                    "
                >
                    {!! strip_tags($content) !!}
                </p>
            @endif

            <form style="display: flex; flex-direction: column; gap: 1rem">
                @forelse ($formFields as $field)
                    <div class="ap-form-field">
                        @if (! empty($field['field_label']))
                            <label
                                class="ap-form-label"
                                style="
                                    display: block;
                                    font-size: var(--mosaic-text-label-lg);
                                    font-weight: 600;
                                    letter-spacing: 0.04em;
                                    text-transform: uppercase;
                                    color: var(--mosaic-on-surface-variant);
                                    margin-bottom: 0.375rem;
                                "
                            >
                                {{ $field['field_label'] }}
                                @if (! empty($field['required']))
                                    <span style="color: var(--mosaic-error)">
                                        *
                                    </span>
                                @endif
                            </label>
                        @endif

                        @if (($field['field_type'] ?? 'text') === 'textarea')
                            <textarea
                                name="{{ $field['field_name'] ?? '' }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="mosaic-input"
                                style="
                                    width: 100%;
                                    min-height: 7rem;
                                    resize: vertical;
                                "
                            ></textarea>
                        @else
                            <input
                                type="{{ $field['field_type'] ?? 'text' }}"
                                name="{{ $field['field_name'] ?? '' }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="mosaic-input"
                                style="width: 100%"
                            />
                        @endif
                    </div>
                @empty
                    <div class="ap-form-field">
                        <label
                            class="ap-form-label"
                            style="
                                display: block;
                                font-size: var(--mosaic-text-label-lg);
                                font-weight: 600;
                                letter-spacing: 0.04em;
                                text-transform: uppercase;
                                color: var(--mosaic-on-surface-variant);
                                margin-bottom: 0.375rem;
                            "
                        >
                            Name
                        </label>
                        <input
                            type="text"
                            placeholder="Your name"
                            class="mosaic-input"
                            style="width: 100%"
                        />
                    </div>
                    <div class="ap-form-field">
                        <label
                            class="ap-form-label"
                            style="
                                display: block;
                                font-size: var(--mosaic-text-label-lg);
                                font-weight: 600;
                                letter-spacing: 0.04em;
                                text-transform: uppercase;
                                color: var(--mosaic-on-surface-variant);
                                margin-bottom: 0.375rem;
                            "
                        >
                            Email
                        </label>
                        <input
                            type="email"
                            placeholder="your@email.com"
                            class="mosaic-input"
                            style="width: 100%"
                        />
                    </div>
                @endforelse

                <div>
                    <button
                        type="submit"
                        class="mosaic-btn mosaic-btn-primary ap-form-submit"
                    >
                        {{ $submitButtonText }}
                    </button>
                </div>
            </form>
        </div>
    </section>
</x-capell-mosaic::widget.wrapper>

<?php
