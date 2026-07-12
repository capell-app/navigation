@props ([
    'item' => [],
    'itemClass',
])
@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Data\NavigationItemData;
    use Capell\Navigation\Support\SafeUrl;

    /** @var NavigationItemData $item */
    $url = isset($item->data['url']) && is_string($item->data['url']) ? (SafeUrl::sanitise($item->data['url']) ?? '') : '';
    $isExternalUrl = preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1;
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesWireNavigate = ($runtimeManifest?->usesWireNavigate ?? false) && ! $isExternalUrl;
    $target = isset($item->data['target']) && is_string($item->data['target']) ? $item->data['target'] : null;
    $rel = isset($item->data['rel']) && is_string($item->data['rel']) && trim($item->data['rel']) !== ''
        ? trim($item->data['rel'])
        : ($target === '_blank' && $isExternalUrl ? 'noopener noreferrer' : null);
@endphp

<li class="capell-navigation-menu-item flex">
    <a
        href="{{ $url }}"
        @if ($usesWireNavigate) wire:navigate @endif
        @class ([
            $itemClass,
            'color-header hover:text-primary focus:text-primary' => ! $item->active,
            'active text-primary' => $item->active,
            $item->data['class'] ?? '',
        ])
        @if ($target !== null) target="{{ $target }}" @endif
        @if ($rel !== null) rel="{{ $rel }}" @endif
    >
        <span
            @class ([
                'lg:order-2',
                'lg:sr-only' => ! empty($item->data['hide_label']),
            ])
        >
            {{ $item->label }}
        </span>

        @if (! empty($item->data['icon']))
            <x-dynamic-component
                :component="$item->active ? ($item->data['active_icon'] ?? str_replace('heroicon-o-', 'heroicon-s-', $item->data['icon'])) : $item->data['icon']"
                class="h-4 w-4 lg:order-1"
            />
        @endif
    </a>
</li>
