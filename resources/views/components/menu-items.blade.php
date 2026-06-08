@php
    use Capell\Navigation\Enums\NavigationItemType;
@endphp

<ul class="capell-menu-items">
    @foreach ($items as $item)
        <li @class(['is-active' => $item->active])>
            @if ($item->type === NavigationItemType::Heading)
                <span>{{ $item->label }}</span>
            @elseif ($item->url !== null)
                <a
                    href="{{ $item->url }}"
                    @if ($item->target !== null) target="{{ $item->target }}" @endif
                    @if ($item->rel !== null) rel="{{ $item->rel }}" @endif
                >
                    {{ $item->label }}
                </a>
            @else
                <span>{{ $item->label }}</span>
            @endif

            @if ($item->children->isNotEmpty())
                @if (($includeNavigationLazyLoader ?? true) !== false && $item->lazyFragmentUrl !== null)
                    <div
                        data-navigation-lazy-fragment
                        data-navigation-fragment-url="{{ $item->lazyFragmentUrl }}"
                    ></div>
                @else
                    @include('capell-navigation::components.menu-items', [
                        'items' => $item->children,
                        'includeNavigationLazyLoader' => $includeNavigationLazyLoader ?? true,
                    ])
                @endif
            @endif
        </li>
    @endforeach
</ul>
