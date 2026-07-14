@props ([
    'itemClass' => null,
    'breakpoint' => \Capell\Navigation\Enums\HeaderNavigationBreakpoint::Lg,
])

<x-capell-navigation::header.main-navigation
    :item-class="trim('capell-header-main-navigation ' . $itemClass)"
    :breakpoint="$breakpoint"
/>
