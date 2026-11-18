@props([
    'item' => [],
    'itemClass',
    'breakpoint' => \Capell\Navigation\Enums\HeaderNavigationBreakpoint::Lg,
])
@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Data\NavigationItemData;
    use Capell\Navigation\Support\SafeIcon;
    use Capell\Navigation\Support\SafeUrl;

    /** @var NavigationItemData $item */
    $url = isset($item->data['url']) && is_string($item->data['url']) ? (SafeUrl::sanitise($item->data['url']) ?? '') : '';
    // A bare "#" means the page has no URL for this language, so the item is text.
    $url = $url === '#' ? '' : $url;
    $isExternalUrl = preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1;
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesWireNavigate = ($runtimeManifest?->usesWireNavigate ?? false) && ! $isExternalUrl;
    $target = isset($item->data['target']) && is_string($item->data['target']) ? $item->data['target'] : null;
    $rel = isset($item->data['rel']) && is_string($item->data['rel']) && trim($item->data['rel']) !== ''
        ? trim($item->data['rel'])
        : ($target === '_blank' && $isExternalUrl ? 'noopener noreferrer' : null);

    // Stored icon names are rendered as components, so only registered Blade Icons names are accepted.
    $icon = SafeIcon::resolve($item->data['icon'] ?? null);
    $activeIcon = SafeIcon::resolve($item->data['active_icon'] ?? null)
        ?? ($icon !== null ? SafeIcon::resolve(str_replace('heroicon-o-', 'heroicon-s-', $icon)) ?? $icon : null);
    // Ancestors of the current page are active but only the exact match is the current page.
    $isCurrent = $item->active && ! collect($item->children)->contains(static fn (mixed $child): bool => $child->active);
@endphp

@php($isLink = $url !== '')

<li class="capell-navigation-menu-item flex">
    {{-- An item without a safe destination is shown as text; an anchor with an empty href would reload the current page. --}}
    <{{ $isLink ? 'a' : 'span' }}
        @if ($isLink) href="{{ $url }}" @endif
        @if ($isLink && $isCurrent) aria-current="page" @endif
        @if ($isLink && $usesWireNavigate) wire:navigate @endif
        @class([
            $itemClass,
            'color-header hover:text-primary focus:text-primary' => ! $item->active,
            'active text-primary' => $item->active,
            $item->data['class'] ?? '',
        ])
        @if ($isLink && $target !== null) target="{{ $target }}" @endif
        @if ($isLink && $rel !== null) rel="{{ $rel }}" @endif
    >
        <span
            class="{{ $breakpoint->itemLabelClasses(! empty($item->data['hide_label'])) }}"
        >
            {{ $item->label }}
        </span>

        @if ($icon !== null)
            <x-dynamic-component
                :component="$item->active ? $activeIcon : $icon"
                class="{{ $breakpoint->itemIconClasses() }}"
            />
        @endif
    </{{ $isLink ? 'a' : 'span' }}>
</li>
