@php
    use Capell\Navigation\Data\NavigationRenderData;
@endphp

@if (($menu ?? null) instanceof NavigationRenderData && $menu->isNotEmpty())
    <nav {{ $attributes->merge(['aria-label' => $navigationLabel]) }}>
        @include ('capell-navigation::components.menu-items', [
            'items' => $menu->items,
        ])
    </nav>
@endif
