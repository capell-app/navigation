<nav {{ $attributes->merge(['aria-label' => $navigationLabel]) }}>
    @include('capell-navigation::components.menu-items', [
        'items' => $menu->items,
    ])
</nav>
