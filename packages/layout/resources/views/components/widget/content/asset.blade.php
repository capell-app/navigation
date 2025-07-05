<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'asset',
    'componentItem',
    'language' => Frontend::getLanguage(),
    'loop',
    'widget',
    'withSummary' => $widget->meta['with_summary'] ?? ($widget->type->meta['with_summary'] ?? true),
])

<x-dynamic-component
    :component="$componentItem"
    :$loop
    :image="$asset->image"
    :link-text="$asset->translation->meta['link_text'] ?? null"
    :parent="$asset->loadParent($language)"
    :tags="$asset->tags"
    :title="$asset->translation->label"
    :summary="$asset->translation->summary"
    :url="$asset->page ? $asset->page->pageUrl->full_url : null"
    :with-summary="$withSummary"
    class="content-resource"
/>

<?php
