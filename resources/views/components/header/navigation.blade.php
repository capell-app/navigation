@php
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;
    use Capell\Navigation\Data\NavigationRenderData;
    use Capell\Navigation\Enums\HeaderNavigationBreakpoint;
    use Capell\Navigation\Models\Navigation;

    $theme = Frontend::theme();
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
    $items = ($menu ?? null) instanceof NavigationRenderData ? $menu->items : collect();
    $breakpoint = ($breakpoint ?? null) instanceof HeaderNavigationBreakpoint ? $breakpoint : HeaderNavigationBreakpoint::Lg;
@endphp

{{--
    Tailwind breakpoint safelist. HeaderNavigationBreakpoint owns selection,
    while these complete static tokens keep both finite variants discoverable.
    dark:lg:bg-transparent lg:!bg-transparent lg:-translate-x-1/2 lg:bg-transparent lg:border-0 lg:divide-none lg:flex lg:flex-nowrap lg:flex-row lg:gap-1 lg:gap-3 lg:gap-x-3 lg:grid lg:grid-cols-1 lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4 lg:grid-cols-[minmax(12rem,18rem)_1fr] lg:h-10 lg:h-auto lg:hidden lg:items-center lg:justify-center lg:justify-end lg:justify-start lg:left-1/2 lg:max-w-none lg:min-w-72 lg:ml-auto lg:mt-0 lg:order-1 lg:order-2 lg:overflow-visible lg:p-0 lg:p-4 lg:px-0 lg:px-4 lg:py-1 lg:py-2 lg:relative lg:rotate-90 lg:rounded-full lg:sr-only lg:static lg:transition-none lg:translate-x-0 lg:visible lg:w-10 lg:w-[min(72rem,calc(100vw-2rem))] lg:w-auto lg:w-max
    max-lg:!translate-x-0 max-lg:!visible max-lg:absolute max-lg:bg-transparent max-lg:border-0 max-lg:bottom-0 max-lg:fixed max-lg:h-dvh max-lg:inset-0 max-lg:invisible max-lg:justify-start max-lg:max-w-[22rem] max-lg:rounded-none max-lg:shadow-none max-lg:translate-x-[-100%] max-lg:w-screen max-lg:z-40
    dark:xl:bg-transparent xl:!bg-transparent xl:-translate-x-1/2 xl:bg-transparent xl:border-0 xl:divide-none xl:flex xl:flex-nowrap xl:flex-row xl:gap-1 xl:gap-3 xl:gap-x-3 xl:grid xl:grid-cols-1 xl:grid-cols-2 xl:grid-cols-3 xl:grid-cols-4 xl:grid-cols-[minmax(12rem,18rem)_1fr] xl:h-10 xl:h-auto xl:hidden xl:items-center xl:justify-center xl:justify-end xl:justify-start xl:left-1/2 xl:max-w-none xl:min-w-72 xl:ml-auto xl:mt-0 xl:order-1 xl:order-2 xl:overflow-visible xl:p-0 xl:p-4 xl:px-0 xl:px-4 xl:py-1 xl:py-2 xl:relative xl:rotate-90 xl:rounded-full xl:sr-only xl:static xl:transition-none xl:translate-x-0 xl:visible xl:w-10 xl:w-[min(72rem,calc(100vw-2rem))] xl:w-auto xl:w-max
    max-xl:!translate-x-0 max-xl:!visible max-xl:absolute max-xl:bg-transparent max-xl:border-0 max-xl:bottom-0 max-xl:fixed max-xl:h-dvh max-xl:inset-0 max-xl:invisible max-xl:justify-start max-xl:max-w-[22rem] max-xl:rounded-none max-xl:shadow-none max-xl:translate-x-[-100%] max-xl:w-screen max-xl:z-40
--}}

@if (($menu ?? null) instanceof NavigationRenderData && $items->isNotEmpty())
    @if ($usesAlpine)
        <script>
            window.capellHeaderNavigation = () => ({
                isMenuOpen: false,
                isClosingMenu: false,
                mobileMenuMediaQuery: null,
                closeMenuListener: null,
                mobileMenuMediaListener: null,
                menuTransitionTimeout: null,
                init() {
                    this.mobileMenuMediaQuery = window.matchMedia(
                        '{{ $breakpoint->mobileMediaQuery() }}',
                    )
                    this.closeMenuListener = () => this.closeMenu()
                    this.mobileMenuMediaListener = () => this.handleMobileMenuMediaChange()

                    this.$watch('isMenuOpen', (value) => {
                        if (!value && !this.isMobileMenuViewport()) {
                            this.isClosingMenu = false
                            this.setPageInert(false)
                            document.body.classList.remove('menu-open')
                            this.dispatchOverlayState()
                            return
                        }

                        this.isClosingMenu = true
                        this.setPageInert(value || this.isClosingMenu)

                        window.clearTimeout(this.menuTransitionTimeout)
                        this.menuTransitionTimeout = window.setTimeout(() => {
                            this.isClosingMenu = false
                            this.setPageInert(this.isMenuOpen)
                            this.dispatchOverlayState()
                        }, 450)

                        document.body.classList.toggle('menu-open', value)
                        this.dispatchOverlayState()
                    })

                    window.addEventListener('close-menu', this.closeMenuListener)
                    this.mobileMenuMediaQuery.addEventListener(
                        'change',
                        this.mobileMenuMediaListener,
                    )

                    this.setPageInert(false)
                },
                destroy() {
                    window.removeEventListener('close-menu', this.closeMenuListener)
                    this.mobileMenuMediaQuery?.removeEventListener(
                        'change',
                        this.mobileMenuMediaListener,
                    )
                    window.clearTimeout(this.menuTransitionTimeout)
                    document.body.classList.remove('menu-open')
                    this.setPageInert(false)
                },
                toggleMenu() {
                    if (this.isMenuOpen) {
                        return this.closeMenu()
                    }

                    return this.openMenu()
                },
                openMenu() {
                    if (this.isMenuOpen) return

                    this.isMenuOpen = true

                    this.$nextTick(() => this.focusFirstMenuItem())
                },
                closeMenu(focusAfter = this.$refs.toggleMenu) {
                    if (!this.isMenuOpen) return

                    this.isMenuOpen = false

                    focusAfter && focusAfter.focus()
                },
                isMobileMenuViewport() {
                    return (
                        this.mobileMenuMediaQuery?.matches ??
                        window.matchMedia('{{ $breakpoint->mobileMediaQuery() }}').matches
                    )
                },
                handleMobileMenuMediaChange() {
                    if (this.isMobileMenuViewport()) {
                        this.setPageInert(this.isMenuOpen || this.isClosingMenu)
                        this.dispatchOverlayState()
                        return
                    }

                    if (this.isMenuOpen) {
                        this.isMenuOpen = false
                        return
                    }

                    this.isClosingMenu = false
                    document.body.classList.remove('menu-open')
                    this.setPageInert(false)
                    this.dispatchOverlayState()
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
                focusableMenuElements() {
                    const elements = [
                        this.$refs.toggleMenu,
                        ...this.$refs.menuPanel.querySelectorAll(
                            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
                        ),
                    ]

                    return elements.filter((element) => {
                        if (!element || element.disabled || element.inert) return false
                        if (element.closest('[inert], [aria-hidden="true"]')) return false
                        if (element.getClientRects().length === 0) return false

                        const style = window.getComputedStyle(element)

                        return style.visibility !== 'hidden' && style.display !== 'none'
                    })
                },
                focusFirstMenuItem() {
                    if (!this.isMobileMenuViewport()) return

                    const elements = this.focusableMenuElements()
                    const firstMenuElement = elements.find(
                        (element) => element !== this.$refs.toggleMenu,
                    )

                    ;(firstMenuElement || this.$refs.menuPanel).focus()
                },
                trapFocus(event) {
                    if (!this.isMenuOpen || !this.isMobileMenuViewport()) return

                    const elements = this.focusableMenuElements()

                    if (elements.length === 0) {
                        event.preventDefault()
                        return
                    }

                    const firstElement = elements[0]
                    const lastElement = elements[elements.length - 1]

                    if (event.shiftKey && document.activeElement === firstElement) {
                        event.preventDefault()
                        lastElement.focus()
                        return
                    }

                    if (!event.shiftKey && document.activeElement === lastElement) {
                        event.preventDefault()
                        firstElement.focus()
                    }
                },
                setPageInert(value) {
                    const shouldInert = value && this.isMobileMenuViewport()
                    const inertAttribute = 'data-capell-navigation-inert'
                    const ariaHiddenAttribute = 'data-capell-navigation-aria-hidden'
                    const applyNavigationInert = (element) => {
                        if (!element.hasAttribute('inert')) {
                            element.setAttribute('inert', '')
                            element.setAttribute(inertAttribute, 'true')
                        }

                        if (element.getAttribute('aria-hidden') !== 'true') {
                            element.setAttribute('aria-hidden', 'true')
                            element.setAttribute(ariaHiddenAttribute, 'true')
                        }
                    }
                    const releaseNavigationInert = (element) => {
                        if (element.getAttribute(inertAttribute) === 'true') {
                            element.removeAttribute('inert')
                            element.removeAttribute(inertAttribute)
                        }

                        if (element.getAttribute(ariaHiddenAttribute) === 'true') {
                            element.removeAttribute('aria-hidden')
                            element.removeAttribute(ariaHiddenAttribute)
                        }
                    }

                    document.querySelectorAll('main, footer').forEach((element) => {
                        if (this.$el.contains(element)) return

                        if (shouldInert) {
                            applyNavigationInert(element)
                            return
                        }

                        releaseNavigationInert(element)
                    })

                    this.$el
                        .closest('header')
                        ?.querySelectorAll(
                            'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])',
                        )
                        .forEach((element) => {
                            if (this.$el.contains(element)) return

                            if (shouldInert) {
                                applyNavigationInert(element)
                                return
                            }

                            releaseNavigationInert(element)
                        })
                },
            })

            document.addEventListener('alpine:init', () => {
                Alpine.data('capellHeaderNavigation', window.capellHeaderNavigation)
            })
        </script>
    @endif

    <div
        @if ($usesAlpine)
            x-data="window.capellHeaderNavigation()"
            x-on:keydown.escape.prevent.stop="closeMenu()"
            x-on:keydown.tab="trapFocus($event)"
        @endif
        class="capell-navigation-header contents"
    >
        @if ($usesAlpine)
            <div class="{{ $breakpoint->menuToggleClasses() }}">
                <button
                    type="button"
                    x-ref="toggleMenu"
                    class="capell-product-menu-toggle toggleMenu color-header hover:text-primary focus:text-primary relative cursor-pointer"
                    :title="isMenuOpen ? '{{ __('capell-frontend::generic.close_menu') }}' : '{{ __('capell-frontend::generic.open_menu') }}'"
                    aria-controls="main-menu"
                    x-bind:aria-expanded="isMenuOpen.toString()"
                    :class="isMenuOpen ? 'is-open' : ''"
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
                    @svg ('heroicon-m-bars-3', 'h-6 w-6', ['x-show' => '!isMenuOpen'])
                    @svg ('heroicon-o-x-mark', 'h-6 w-6', ['x-cloak' => '', 'x-show' => 'isMenuOpen'])
                </button>
            </div>
        @endif

        <div
            id="menu"
            @class ([
                $breakpoint->menuWrapperClasses(),
                $breakpoint->mobileInvisibleClass() => $usesAlpine,
                'visible' => ! $usesAlpine,
            ])
            @if ($usesAlpine)
                x-bind:class="
                    isMenuOpen || isClosingMenu ? 'visible' : '{{ $breakpoint->mobileInvisibleClass() }}'
                "
            @endif
        >
            @if ($usesAlpine)
                <div
                    class="{{ $breakpoint->backdropClasses() }}"
                    x-on:click="closeMenu($refs.toggleMenu)"
                ></div>
            @endif

            <nav
                id="main-menu"
                tabindex="-1"
                x-ref="menuPanel"
                aria-label="{{ __('capell-navigation::generic.main_navigation') }}"
                @class ([
                    $breakpoint->navbarClasses(),
                    $breakpoint->navbarInvisibleClasses() => $usesAlpine,
                    'visible static max-w-none' => ! $usesAlpine,
                ])
                @if ($usesAlpine)
                    x-bind:class="
                        isMenuOpen
                            ? '{{ $breakpoint->navbarOpenClasses() }}'
                            : isClosingMenu
                              ? '{{ $breakpoint->navbarClosingClasses() }}'
                              : '{{ $breakpoint->navbarClosedClasses() }}'
                    "
                @endif
            >
                <ul
                    @class ([
                        $breakpoint->navItemsClasses(),
                        $breakpoint->alignmentClass((string) $theme->getMeta('header_menu_alignment', 'right')),
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
                                :breakpoint="$breakpoint"
                            />
                        @else
                            <x-capell-navigation::header.menu.item
                                :id="$id"
                                :item="$item"
                                :navigation="$navigation"
                                :index="$loop->index"
                                :item-class="$itemClass"
                                :breakpoint="$breakpoint"
                            />
                        @endif
                    @endforeach
                </ul>

                @if ($theme->getMeta('dark_mode_toggle'))
                    <div class="{{ $breakpoint->darkModeWrapperClasses() }}">
                        <button
                            type="button"
                            class="{{ $breakpoint->darkModeButtonClasses() }}"
                            aria-label="{{ __('capell-frontend::generic.dark_mode') }}"
                            @if ($usesAlpine)
                                x-on:click="toggleDarkMode"
                                x-bind:aria-label="
                                    isDarkMode
                                        ? '{{ __('capell-frontend::generic.light_mode') }}'
                                        : '{{ __('capell-frontend::generic.dark_mode') }}'
                                "
                                x-bind:title="
                                    isDarkMode
                                        ? '{{ __('capell-frontend::generic.dark_mode') }}'
                                        : '{{ __('capell-frontend::generic.light_mode') }}'
                                "
                            @endif
                        >
                            <span
                                class="{{ $breakpoint->mobileOnlyClass() }}"
                                @if ($usesAlpine)
                                    x-text="
                                        isDarkMode
                                            ? '{{ __('capell-frontend::generic.light_mode') }}'
                                            : '{{ __('capell-frontend::generic.dark_mode') }}'
                                    "
                                @endif
                            >
                                {{ __('capell-frontend::generic.dark_mode') }}
                            </span>

                            <span class="ml-auto">
                                @svg ('heroicon-o-sun', 'hidden h-4 w-4 md:h-5 md:w-5 dark:block')
                                @svg ('heroicon-o-moon', 'h-4 w-4 stroke-current md:h-5 md:w-5 dark:hidden')
                            </span>
                        </button>
                    </div>
                @endif

                {!!
                    app(RenderHookRegistry::class)->renderAll(
                        RenderHookLocation::HeaderAfter,
                        scenario: 'theme-foundation-header-actions',
                        target: 'capell-navigation::components.header.navigation',
                    )
                !!}
            </nav>
        </div>
    </div>
@endif
