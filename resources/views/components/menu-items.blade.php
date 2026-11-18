@php
    use Capell\Navigation\Enums\NavigationItemType;
    use Capell\Navigation\Support\SafeUrl;
@endphp

<ul class="capell-menu-items">
    @foreach ($items as $item)
        @php($safeUrl = SafeUrl::sanitise($item->url))
        {{-- A bare "#" means the page has no URL for this language; it is text, not a link to the top of the page. --}}
        @php($safeUrl = $safeUrl === '#' ? null : $safeUrl)
        <li @class(['is-active' => $item->active])>
            @if ($item->type === NavigationItemType::Heading)
                <span>{{ $item->label }}</span>
            @elseif ($safeUrl !== null)
                <a
                    href="{{ $safeUrl }}"
                    @if ($item->active && ! collect($item->children)->contains(static fn ($child): bool => $child->active)) aria-current="page" @endif
                    @if ($item->target !== null) target="{{ $item->target }}" @endif
                    @if ($item->rel !== null) rel="{{ $item->rel }}" @endif
                >
                    {{ $item->label }}
                </a>
            @else
                <span>{{ $item->label }}</span>
            @endif

            @if ($item->children->isNotEmpty())
                @include('capell-navigation::components.menu-items', ['items' => $item->children])
            @endif
        </li>
    @endforeach
</ul>
