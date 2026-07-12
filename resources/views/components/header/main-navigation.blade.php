@props ([
    'itemClass' => null,
])

<x-capell-navigation::header.main-navigation
    :item-class="trim('capell-header-main-navigation ' . $itemClass)"
/>
