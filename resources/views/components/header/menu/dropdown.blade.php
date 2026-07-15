@props ([
    'id' => null,
    'itemClass',
    'dropdownItemClass' => 'capell-product-dropdown-item disabled:cursor-not-allowed disabled:opacity-50',
    'dropdownName' => 'header-menu',
    'navigation',
    'item',
    'breakpoint' => \Capell\Navigation\Enums\HeaderNavigationBreakpoint::Lg,
])
@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Navigation\Data\NavigationItemData;
    use Capell\Navigation\Enums\NavigationDropdownLayout;
    use Capell\Navigation\Support\SafeUrl;

    /** @var NavigationItemData $item */
    $currentDropdownName = $dropdownName . '-' . ($id !== null ? (string) $id : hash('sha256', $item->label));
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
    $usesWireNavigate = $runtimeManifest?->usesWireNavigate ?? false;
    $dropdownLayout = ($item->data['dropdown_layout'] ?? NavigationDropdownLayout::Dropdown->value) === NavigationDropdownLayout::Mega->value
        ? NavigationDropdownLayout::Mega
        : NavigationDropdownLayout::Dropdown;
    $megaColumns = max(1, min(4, is_numeric($item->data['mega_columns'] ?? null) ? (int) $item->data['mega_columns'] : 3));
    $megaColumnClass = $breakpoint->megaColumnClass($megaColumns);
    $hasMegaPanel = $dropdownLayout === NavigationDropdownLayout::Mega
        && (! empty($item->data['mega_panel_heading']) || ! empty($item->data['mega_panel_description']));
@endphp

@if (! $usesAlpine)
    <li class="{{ $breakpoint->dropdownWithoutAlpineClasses() }}">
        <a
            href="{{ SafeUrl::sanitise($item->data['url'] ?? null) ?? '#' }}"
            @class ([
                $itemClass,
                'hover:text-primary focus:text-primary' => ! $item->active,
                'active text-primary' => $item->active,
                $item->data['class'] ?? '',
            ])
            @if (!empty($item->data['target'])) target="{{ $item->data['target'] }}" @endif
        >
            <span>{{ $item->label }}</span>
        </a>

        <ul class="{{ $breakpoint->dropdownChildrenClasses() }}">
            @foreach ($item->children as $id => $child)
                @if ($child->children->count() > 0)
                    @include ('capell-navigation::components.header.menu.dropdown', [
                        'id' => $id,
                        'dropdownName' => $currentDropdownName,
                        'item' => $child,
                        'navigation' => $navigation,
                        'index' => $loop->index,
                        'itemClass' => $itemClass,
                        'breakpoint' => $breakpoint,
                    ])
                @else
                    <x-capell-navigation::header.menu.item
                        :id="$id"
                        :item="$child"
                        :navigation="$navigation"
                        :index="$loop->index"
                        :item-class="$itemClass"
                        :breakpoint="$breakpoint"
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
            $breakpoint->dropdownPanelClasses(),
            $breakpoint->dropdownWidthClasses() => $dropdownLayout === NavigationDropdownLayout::Dropdown,
            $breakpoint->megaDropdownClasses() => $dropdownLayout === NavigationDropdownLayout::Mega,
        ])
        container-tag="li"
        :container-class="$breakpoint->dropdownContainerClasses()"
        panel-tag="div"
        panel-click-outside="window.matchMedia('{{ $breakpoint->desktopMediaQuery() }}').matches ? close($refs['{{ $currentDropdownName }}_toggle']) : null"
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
                @class ([
                    $breakpoint->hiddenLabelClasses() => ! empty($item->data['hide_label']),
                ])
            >
                {{ $item->label }}
            </span>

            @svg ('heroicon-o-chevron-right', $breakpoint->chevronClasses())
        </x-slot:trigger>

        <ul class="flex flex-col gap-1">
            <li
                class="nav-item-dropdown-header border-b border-gray-200 pb-1 {{ $breakpoint->mobileOnlyClass() }} dark:border-gray-700"
            >
                <button
                    type="button"
                    @class ([
                        $dropdownItemClass,
                        'hover:text-primary focus:text-primary font-semibold',
                    ])
                    x-on:click="close($refs['{{ $currentDropdownName }}_toggle'])"
                >
                    @svg ('heroicon-o-arrow-left', 'mr-1 h-5 w-5 stroke-current')
                    <span> {{ $item->label }} </span>
                </button>
            </li>
        </ul>

        <div
            @class ([
                'flex flex-col gap-1',
                $breakpoint->megaGridClasses() => $dropdownLayout === NavigationDropdownLayout::Mega,
                $megaColumnClass => $dropdownLayout === NavigationDropdownLayout::Mega && ! $hasMegaPanel,
                $breakpoint->megaPanelGridClasses() => $hasMegaPanel,
            ])
            @if ($dropdownLayout === NavigationDropdownLayout::Mega) data-capell-navigation-mega-menu @endif
        >
            @if ($hasMegaPanel)
                <div class="rounded-lg bg-slate-50 p-4 dark:bg-slate-900">
                    @if (! empty($item->data['mega_panel_heading']))
                        <p
                            class="text-sm font-semibold text-slate-950 dark:text-white"
                        >
                            {{ $item->data['mega_panel_heading'] }}
                        </p>
                    @endif

                    @if (! empty($item->data['mega_panel_description']))
                        <p
                            class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300"
                        >
                            {{ $item->data['mega_panel_description'] }}
                        </p>
                    @endif

                    @if (! empty($item->data['mega_panel_url']))
                        <a
                            href="{{ SafeUrl::sanitise($item->data['mega_panel_url']) ?? '#' }}"
                            class="text-primary mt-3 inline-flex text-sm font-semibold hover:underline focus:underline"
                            @if ($usesWireNavigate) wire:navigate @endif
                        >
                            {{ $item->label }}
                        </a>
                    @endif
                </div>
            @endif

            <ul
                @class ([
                    'flex flex-col gap-1',
                    $breakpoint->megaChildrenGridClasses() => $dropdownLayout === NavigationDropdownLayout::Mega,
                    $megaColumnClass => $dropdownLayout === NavigationDropdownLayout::Mega && $hasMegaPanel,
                ])
            >
                @foreach ($item->children as $id => $child)
                    @if ($child->children->count() > 0)
                        @include ('capell-navigation::components.header.menu.dropdown', [
                            'id' => $id,
                            'dropdownName' => $currentDropdownName,
                            'item' => $child,
                            'navigation' => $navigation,
                            'index' => $loop->index,
                            'breakpoint' => $breakpoint,
                        ])
                    @else
                        <li class="nav-item">
                            <a
                                href="{{ SafeUrl::sanitise($child->data['url'] ?? null) ?? '' }}"
                                @if (!empty($child->data['target'])) target="{{ $child->data['target'] }}" @endif
                                @if ($usesWireNavigate) wire:navigate @endif
                                @class ([
                                    $dropdownItemClass,
                                    'hover:text-primary focus:text-primary' => ! $child->active,
                                    'active text-primary dark:text-primary' => $child->active,
                                    $child->data['class'] ?? '',
                                ])
                            >
                                <span> {{ $child->label }} </span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </x-capell::dropdown>
@endif
