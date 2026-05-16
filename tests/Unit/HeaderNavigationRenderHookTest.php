<?php

declare(strict_types=1);

it('registers the foundation header navigation render hook from the navigation package', function (): void {
    $provider = file_get_contents(dirname(__DIR__, 2) . '/src/Providers/NavigationServiceProvider.php');
    $hook = file_get_contents(dirname(__DIR__, 2) . '/src/Support/RenderHooks/RegisterFoundationHeaderNavigationHook.php');

    expect($provider)->toContain('registerFrontendRenderHooks()')
        ->and($provider)->toContain('new RegisterFoundationHeaderNavigationHook')
        ->and($provider)->toContain('afterResolving')
        ->and($provider)->toContain('registerFrontendRenderHooksForRegistry')
        ->and($hook)->toContain('frontend-default-primary-navigation')
        ->and($hook)->toContain('foundation-theme-primary-navigation')
        ->and($hook)->toContain('capell::header.index')
        ->and($hook)->toContain('capell-navigation::components.header.main-navigation');
});

it('owns accessible header menu controls in the navigation package', function (): void {
    $navigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/navigation.blade.php');

    expect($navigation)->toContain('aria-controls="main-menu"')
        ->and($navigation)->toContain('x-bind:aria-expanded="isMenuOpen.toString()"')
        ->and($navigation)->toContain('capell-product-menu-toggle')
        ->and($navigation)->toContain('x-text=')
        ->and($navigation)->toContain("isMenuOpen\n                            ? '{{ __('capell-frontend::generic.close_menu') }}'")
        ->and($navigation)->toContain(": '{{ __('capell-frontend::generic.open_menu') }}'")
        ->and($navigation)->toContain('toggleMenu()')
        ->and($navigation)->toContain('capell-navigation-menu-open-changed')
        ->and($navigation)->not->toContain("\$refs.toggleMenu.setAttribute('aria-expanded', 'true')");
});
