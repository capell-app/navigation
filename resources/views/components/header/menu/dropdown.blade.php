@props([
    'id' => null,
    'itemClass',
    'dropdownItemClass' => 'capell-product-dropdown-item disabled:cursor-not-allowed disabled:opacity-50',
    'dropdownName' => 'header-menu',
    'navigation',
    'item',
])
@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Data\NavigationItemData;
    use Capell\Navigation\Enums\NavigationDropdownLayout;

    /** @var NavigationItemData $item */
    $currentDropdownName = $dropdownName . '-' . ($id !== null ? (string) $id : hash('sha256', $item->label));
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
    $usesWireNavigate = $runtimeManifest?->usesWireNavigate ?? false;
    $dropdownLayout = ($item->data['dropdown_layout'] ?? NavigationDropdownLayout::Dropdown->value) === NavigationDropdownLayout::Mega->value
        ? NavigationDropdownLayout::Mega
        : NavigationDropdownLayout::Dropdown;
    $megaColumns = max(1, min(4, is_numeric($item->data['mega_columns'] ?? null) ? (int) $item->data['mega_columns'] : 3));
    $megaColumnClass = match ($megaColumns) {
        1 => 'lg:grid-cols-1',
        2 => 'lg:grid-cols-2',
        4 => 'lg:grid-cols-4',
        default => 'lg:grid-cols-3',
    };
    $hasMegaPanel = $dropdownLayout === NavigationDropdownLayout::Mega
        && (! empty($item->data['mega_panel_heading']) || ! empty($item->data['mega_panel_description']));
@endphp

@if (! $usesAlpine)
    <li class="capell-navigation-menu-dropdown flex flex-col lg:flex-row">
        <a
            href="{{ $item->data['url'] ?? '#' }}"
            @class([
                $itemClass,
                'hover:text-primary focus:text-primary' => ! $item->active,
                'active text-primary' => $item->active,
                $item->data['class'] ?? '',
            ])
            @if (!empty($item->data['target'])) target="{{ $item->data['target'] }}" @endif
        >
            <span>{{ $item->label }}</span>
        </a>

        <ul class="flex flex-col lg:flex-row">
            @foreach ($item->children as $id => $child)
                @if ($child->children->count() > 0)
                    @include('capell-navigation::components.header.menu.dropdown', [
                        'id' => $id,
                        'dropdownName' => $currentDropdownName,
                        'item' => $child,
                        'navigation' => $navigation,
                        'index' => $loop->index,
                        'itemClass' => $itemClass,
                    ])
                @else
                    <x-capell-navigation::header.menu.item
                        :id="$id"
                        :item="$child"
                        :navigation="$navigation"
                        :index="$loop->index"
                        :item-class="$itemClass"
                    />
                @endif
            @endforeach
        </ul>
    </li>
@else
    <x-capell::dropdown
        :name="$currentDropdownName"
        background="bg-white"
        @class([
            'rounded-xl border border-slate-200 p-2 shadow-xl shadow-slate-900/10 max-lg:inset-0 max-lg:rounded-none max-lg:border-0 max-lg:shadow-none',
            'lg:w-max lg:min-w-72' => $dropdownLayout === NavigationDropdownLayout::Dropdown,
            'lg:left-1/2 lg:w-[min(72rem,calc(100vw-2rem))] lg:-translate-x-1/2 lg:p-4' => $dropdownLayout === NavigationDropdownLayout::Mega,
        ])
        container-tag="li"
        container-class="group flex lg:relative"
        panel-tag="div"
        panel-click-outside="window.matchMedia('(min-width: {{ config('capell-frontend.breakpoints.lg') }}px)').matches ? close($refs['{{ $currentDropdownName }}_toggle']) : null"
        panel-hidden-class="pointer-events-none invisible opacity-0"
        panel-visible-class="visible opacity-100"
        :stop-trigger-click-propagation="true"
        trigger-click="toggle()"
        trigger-type="button"
        :use-float="false"
        x-on:focusin.window="! $refs['{{ $currentDropdownName }}_dropdown'].contains($event.target) && close()"
        x-on:keydown.escape.prevent.stop="close($refs['{{ $currentDropdownName }}_toggle'])"
    >
        <x-slot:trigger
            @class([
                $itemClass,
                'hover:text-primary focus:text-primary' => ! $item->active,
                'active text-primary' => $item->active,
                $item->data['class'] ?? '',
            ])
        >
            @if (! empty($item->data['icon']))
                <x-dynamic-component
                    class="h-6 w-6"
                    :component="$item->data['icon']"
                />
            @endif

            <span
                @class([
                    'mr-1 lg:sr-only' => ! empty($item->data['hide_label']),
                ])
            >
                {{ $item->label }}
            </span>

            @svg('heroicon-o-chevron-right', '-mr-2 ml-auto h-4 w-4 text-gray-400 group-hover:text-inherit group-focus:text-inherit lg:rotate-90')
        </x-slot>

        <ul class="flex flex-col gap-1">
            <li
                class="nav-item-dropdown-header border-b border-gray-200 pb-1 lg:hidden dark:border-gray-700"
            >
                <button
                    type="button"
                    @class([
                        $dropdownItemClass,
                        'hover:text-primary focus:text-primary font-semibold',
                    ])
                    x-on:click="close($refs['{{ $currentDropdownName }}_toggle'])"
                >
                    @svg('heroicon-o-arrow-left', 'mr-1 h-5 w-5 stroke-current')
                    <span>
                        {{ $item->label }}
                    </span>
                </button>
            </li>
        </ul>

        <div
            @class([
                'flex flex-col gap-1',
                'lg:grid lg:gap-3' => $dropdownLayout === NavigationDropdownLayout::Mega,
                $megaColumnClass => $dropdownLayout === NavigationDropdownLayout::Mega && ! $hasMegaPanel,
                'lg:grid-cols-[minmax(12rem,18rem)_1fr]' => $hasMegaPanel,
            ])
            @if ($dropdownLayout === NavigationDropdownLayout::Mega) data-capell-navigation-mega-menu @endif
        >
            @if ($hasMegaPanel)
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-900">
                    @if (! empty($item->data['mega_panel_heading']))
                        <p class="text-sm font-semibold text-slate-950 dark:text-white">
                            {{ $item->data['mega_panel_heading'] }}
                        </p>
                    @endif

                    @if (! empty($item->data['mega_panel_description']))
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ $item->data['mega_panel_description'] }}
                        </p>
                    @endif

                    @if (! empty($item->data['mega_panel_url']))
                        <a
                            href="{{ $item->data['mega_panel_url'] }}"
                            class="text-primary mt-3 inline-flex text-sm font-semibold hover:underline focus:underline"
                            @if ($usesWireNavigate) wire:navigate @endif
                        >
                            {{ $item->label }}
                        </a>
                    @endif
                </div>
            @endif

            <ul
                @class([
                    'flex flex-col gap-1',
                    'lg:grid lg:gap-1' => $dropdownLayout === NavigationDropdownLayout::Mega,
                    $megaColumnClass => $dropdownLayout === NavigationDropdownLayout::Mega && $hasMegaPanel,
                ])
            >
                @foreach ($item->children as $id => $child)
                    @if ($child->children->count() > 0)
                        @include('capell-navigation::components.header.menu.dropdown', [
                            'id' => $id,
                            'dropdownName' => $currentDropdownName,
                            'item' => $child,
                            'navigation' => $navigation,
                            'index' => $loop->index,
                        ])
                    @else
                        <li class="nav-item">
                            <a
                                href="{{ $child->data['url'] ?? '' }}"
                                @if (!empty($child->data['target'])) target="{{ $child->data['target'] }}" @endif
                                @if ($usesWireNavigate) wire:navigate @endif
                                @class([
                                    $dropdownItemClass,
                                    'hover:text-primary focus:text-primary' => ! $child->active,
                                    'active text-primary dark:text-primary' => $child->active,
                                    $child->data['class'] ?? '',
                                ])
                            >
                                <span>
                                    {{ $child->label }}
                                </span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </x-capell::dropdown>
@endif
