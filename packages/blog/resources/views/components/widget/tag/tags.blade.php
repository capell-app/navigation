<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $language = Frontend::language();
    $site = Frontend::site();
    $theme = Frontend::theme();
    $page = Frontend::page();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'showPageContent' => $widgetData['meta']['show_page_content'] ?? false,
    'showPageTitle' => $widgetData['meta']['show_page_title'] ?? false,
    'loop',
    'widget',
])
<x-capell-layout::widget.wrapper
    class="widget-tags"
    :$container
    :$containerKey
    :$containerWidth
    :$containerWidth
    :index="$loop->index"
    :$widget
>
    @if (($widget->translation && ($widget->translation->title || $widget->translation->content))
         || ($showPageContent && $page->translation->title)
         || ($showPageTitle && $page->translation->content))
        <x-capell::content
            class="mb-4"
            :compact="true"
            :content="$widget->translation->content ?? ($showPageContent ? $page->translation->content : null)"
            :content-type="$widget->translation->content ? $widget->type->content_structure : ($showPageContent ? $page->type->content_structure : null)"
            :muted="in_array($containerKey, $theme->secondary_containers)"
            :text-align="$widget->meta['align'] ?? $widget->type->meta['align'] ?? null"
            :title="$widget->translation->title ?? ($showPageTitle ? $page->translation->title : null)"
            :heading-style="($widget->meta['heading_style'] ?? null) ?: $widget->type->meta['heading_style'] ?? null"
        />
    @endif

    @if ($tags->isEmpty())
        <x-capell::no-results>
            {{ __('capell-blog::messages.no_tags_found') }}
        </x-capell::no-results>
    @else
        <ul class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getPageUrl($tagPage, $language))
                <li>
                    <x-capell-blog::tag :$url>
                        {{ $tag->getTranslation('name', $language->code) }}
                        <x-slot:count>
                            ({{ $tag->pages_count }})
                        </x-slot>
                    </x-capell-blog::tag>
                </li>
            @endforeach
        </ul>
    @endif
    @if (method_exists($tags, 'total') && $tags->hasPages())
        <x-capell::pagination
            :results="$tags"
            :scrollToElement="$containerKey . '-' . $widget->key . '-' . $loop->index"
        />
    @endif
</x-capell-layout::widget.wrapper>

<?php
