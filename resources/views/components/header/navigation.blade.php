@php
    use Capell\Frontend\Facades\Frontend;

    $theme = Frontend::theme();
@endphp

<div
    x-data="capellHeaderNavigation"
    x-on:keydown.escape.prevent.stop="closeMenu()"
    class="contents"
>
    <div
        class="absolute right-4 top-[max(2vh,14px)] z-50 flex items-center justify-end gap-x-2 md:gap-x-1 lg:hidden"
    >
        <button
            type="button"
            x-ref="toggleMenu"
            class="toggleMenu color-header hover:text-primary focus:text-primary relative flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-600/75"
            :title="isMenuOpen ? '{{ __('capell-frontend::generic.close_menu') }}' : '{{ __('capell-frontend::generic.open_menu') }}'"
            aria-controls="main-menu"
            x-bind:aria-expanded="isMenuOpen.toString()"
            :class="isMenuOpen ? 'bg-primary text-white hover:text-white hover:opacity-80 focus:text-white' : ''"
            x-on:click="toggleMenu"
        >
            <span
                class="sr-only"
                x-text="
                    isMenuOpen
                        ? '{{ __('capell-frontend::generic.close_menu') }}'
                        : '{{ __('capell-frontend::generic.open_menu') }}'
                "
            ></span>
            @svg('heroicon-m-bars-3', 'h-6 w-6', ['x-show' => '!isMenuOpen'])
            @svg('heroicon-o-x-mark', 'h-6 w-6', ['x-cloak' => '', 'x-show' => 'isMenuOpen'])
        </button>
    </div>

    <div
        id="menu"
        class="menu-wrapper max-lg:height-full relative flex h-full w-full grow justify-center bg-[var(--bg-color-header)] max-lg:bg-transparent lg:visible lg:w-auto lg:!bg-transparent"
        x-bind:class="isMenuOpen || isClosingMenu ? 'visible' : 'invisible'"
    >
        <div
            class="menu-backdrop absolute left-0 top-0 z-30 h-full w-full bg-black/75 invert-[.25] lg:hidden"
            x-on:click="closeMenu($refs.toggleMenu)"
        ></div>
        <nav
            id="main-menu"
            class="navbar invisible absolute left-0 top-0 z-40 flex h-full w-full max-w-md transform flex-col overflow-y-auto overflow-x-hidden border-t border-gray-100 bg-white transition-[translate,visibility] duration-500 ease-in-out lg:visible lg:static lg:max-w-none lg:translate-x-0 lg:flex-row lg:overflow-visible lg:border-0 lg:bg-transparent lg:transition-none dark:border-gray-700 dark:bg-gray-950 dark:lg:bg-transparent"
            x-bind:class="isMenuOpen ? 'visible translate-x-0' : 'invisible translate-x-[-100%]'"
        >
            <ul
                @class([
                    'nav-items relative flex w-full flex-col flex-wrap justify-center gap-y-0.5 pt-6 lg:static lg:flex-row lg:space-x-2 lg:py-3',
                    'lg:justify-start' => $theme->getMeta('header_menu_alignment') === 'left',
                    'lg:justify-center' => $theme->getMeta('header_menu_alignment') === 'center',
                    'lg:justify-end' => $theme->getMeta('header_menu_alignment') === 'right',
                ])
            >
                @foreach ($items as $id => $item)
                    @if ($item->children->count() > 0)
                        <x-capell-navigation::header.menu.dropdown
                            :id="$id"
                            :item="$item"
                            :navigation="$navigation"
                            :index="$loop->index"
                            :item-class="$itemClass"
                        />
                    @else
                        <x-capell-navigation::header.menu.item
                            :id="$id"
                            :item="$item"
                            :navigation="$navigation"
                            :index="$loop->index"
                            :item-class="$itemClass"
                        />
                    @endif
                @endforeach
            </ul>

            @if ($theme->getMeta('dark_mode_toggle'))
                <div
                    class="grid grid-cols-2 items-center justify-between gap-x-2 border-t border-gray-100 p-4 lg:mt-0 lg:flex lg:h-auto lg:gap-x-3 lg:divide-none lg:border-0 lg:px-0 lg:py-2 dark:border-gray-700"
                >
                    <button
                        class="hover:text-primary flex h-auto w-full cursor-pointer justify-between rounded-lg border border-gray-100 px-3 py-3 lg:w-auto dark:border-gray-600"
                        x-on:click="toggleDarkMode"
                        x-tooltip="
                            isDarkMode
                                ? '{{ __('capell-frontend::generic.dark_mode') }}'
                                : '{{ __('capell-frontend::generic.light_mode') }}'
                        "
                        tabindex="0"
                    >
                        <span
                            class="lg:hidden"
                            x-text="
                                isDarkMode
                                    ? '{{ __('capell-frontend::generic.light_mode') }}'
                                    : '{{ __('capell-frontend::generic.dark_mode') }}'
                            "
                        ></span>

                        <span class="ml-auto">
                            @svg('heroicon-o-sun', 'hidden h-4 w-4 md:h-5 md:w-5 dark:block')
                            @svg('heroicon-o-moon', 'h-4 w-4 stroke-current md:h-5 md:w-5 dark:hidden')
                        </span>
                    </button>
                </div>
            @endif
        </nav>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('capellHeaderNavigation', () => ({
                isMenuOpen: false,
                isClosingMenu: false,
                init() {
                    this.$watch('isMenuOpen', (value) => {
                        this.isClosingMenu = true

                        setTimeout(() => {
                            this.isClosingMenu = false
                            this.dispatchOverlayState()
                        }, 450)

                        document.body.classList.toggle('menu-open', value)
                        this.dispatchOverlayState()
                    })

                    window.addEventListener('close-menu', () => {
                        this.isMenuOpen = false
                    })
                },
                toggleMenu() {
                    if (this.isMenuOpen) {
                        return this.closeMenu()
                    }

                    return this.openMenu()
                },
                openMenu() {
                    if (this.isMenuOpen) return

                    this.$refs.toggleMenu.focus()

                    this.isMenuOpen = true
                },
                closeMenu(focusAfter) {
                    if (!this.isMenuOpen) return

                    this.isMenuOpen = false

                    focusAfter && focusAfter.focus()
                },
                dispatchOverlayState() {
                    window.dispatchEvent(
                        new CustomEvent('capell-navigation-menu-open-changed', {
                            detail: {
                                open: this.isMenuOpen || this.isClosingMenu,
                            },
                        }),
                    )
                },
            }))
        })
    </script>
@endpush
