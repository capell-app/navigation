<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Symfony\Component\Process\Process;

it('registers the foundation header navigation render hook from the navigation package', function (): void {
    $provider = navigationFileContents(dirname(__DIR__, 2) . '/src/Providers/NavigationServiceProvider.php');
    $hook = navigationFileContents(dirname(__DIR__, 2) . '/src/Support/RenderHooks/RegisterFoundationHeaderNavigationHook.php');

    expect($provider)->toContain('registerFrontendRenderHooks')
        ->and($provider)->toContain('new RegisterFoundationHeaderNavigationHook')
        ->and($provider)->toContain('afterResolving')
        ->and($provider)->toContain('registerFrontendRenderHooksForRegistry')
        ->and($hook)->toContain('frontend-default-primary-navigation')
        ->and($hook)->toContain('theme-foundation-primary-navigation')
        ->and($hook)->toContain('capell::header.index')
        ->and($hook)->toContain('capell-navigation::components.header.main-navigation');
});

it('owns accessible header menu controls in the navigation package', function (): void {
    $navigation = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/navigation.blade.php');

    expect($navigation)->toContain('aria-controls="main-menu"')
        ->and($navigation)->toContain('use Capell\Frontend\Enums\RenderHookLocation;')
        ->and($navigation)->toContain('use Capell\Frontend\Support\Render\RenderHookRegistry;')
        ->and($navigation)->toContain('x-bind:aria-expanded="isMenuOpen.toString()"')
        ->and($navigation)->toContain('capell-product-menu-toggle')
        ->and($navigation)->toContain('x-text=')
        ->and($navigation)->toContain('isMenuOpen')
        ->and($navigation)->toContain("{{ __('capell-frontend::generic.close_menu') }}")
        ->and($navigation)->toContain("{{ __('capell-frontend::generic.open_menu') }}")
        ->and($navigation)->toContain('toggleMenu()')
        ->and($navigation)->toContain('trapFocus(event)')
        ->and($navigation)->toContain('setPageInert(value)')
        ->and($navigation)->toContain("element.setAttribute('inert', '')")
        ->and($navigation)->toContain("element.setAttribute(inertAttribute, 'true')")
        ->and($navigation)->toContain("element.setAttribute(ariaHiddenAttribute, 'true')")
        ->and($navigation)->toContain("if (element.getAttribute(inertAttribute) === 'true')")
        ->and($navigation)->toContain('element.getAttribute(ariaHiddenAttribute)')
        ->and($navigation)->toContain('menuTransitionTimeout')
        ->and($navigation)->toContain('window.clearTimeout(this.menuTransitionTimeout)')
        ->and($navigation)->toContain('isClosingMenu')
        ->and($navigation)->toContain("'max-lg:!visible max-lg:translate-x-[-100%]'")
        ->and($navigation)->toContain('x-on:keydown.tab="trapFocus($event)"')
        ->and($navigation)->toContain('capell-navigation-menu-open-changed')
        ->and($navigation)->toContain('x-bind:aria-label=')
        ->and($navigation)->not->toContain("\$refs.toggleMenu.setAttribute('aria-expanded', 'true')");
});

it('lets active header item icons inherit the active link colour', function (): void {
    $item = file_get_contents(dirname(__DIR__, 2) . '/resources/views/components/header/menu/item.blade.php');

    expect($item)->toContain("'active text-primary' => \$item->active")
        ->and($item)->not->toContain("'text-primary' => \$item->active");
});

it('only releases mobile menu inert attributes owned by navigation', function (): void {
    $nodeVersion = new Process(['node', '--version']);
    $nodeVersion->run();

    if (! $nodeVersion->isSuccessful()) {
        $this->markTestSkipped('Node.js is required to run the navigation behavior test.');
    }

    $navigation = navigationFileContents(dirname(__DIR__, 2) . '/resources/views/components/header/navigation.blade.php');

    $matchCount = preg_match('#<script>\s*(.*?)\s*document\.addEventListener#s', $navigation, $matches);

    expect($matchCount)->toBe(1);

    throw_if($matchCount !== 1 || ! isset($matches[1]), RuntimeException::class, 'Expected navigation Blade file to contain the mobile menu script.');

    $navigationScript = base64_encode($matches[1]);
    $nodeScript = <<<JS
        const assert = require('assert')
        const source = Buffer.from('{$navigationScript}', 'base64').toString('utf8')

        class Element {
            constructor(name, attributes = {}, children = []) {
                this.name = name
                this.attributes = { ...attributes }
                this.children = children
                this.disabled = false
                this.inert = Object.prototype.hasOwnProperty.call(this.attributes, 'inert')
            }

            contains(element) {
                return element === this || this.children.includes(element)
            }

            closest(selector) {
                return selector === 'header' ? header : null
            }

            querySelectorAll() {
                return this.children
            }

            setAttribute(name, value) {
                this.attributes[name] = String(value)

                if (name === 'inert') {
                    this.inert = true
                }
            }

            removeAttribute(name) {
                delete this.attributes[name]

                if (name === 'inert') {
                    this.inert = false
                }
            }

            getAttribute(name) {
                return Object.prototype.hasOwnProperty.call(this.attributes, name)
                    ? this.attributes[name]
                    : null
            }

            hasAttribute(name) {
                return Object.prototype.hasOwnProperty.call(this.attributes, name)
            }
        }

        const existingMain = new Element('main', { inert: '', 'aria-hidden': 'true' })
        const navigationOwnedMain = new Element('main')
        const navigationOwnedFooter = new Element('footer')
        const existingHeaderButton = new Element('button', { inert: '', 'aria-hidden': 'true' })
        const navigationOwnedHeaderLink = new Element('a')
        const header = new Element('header', {}, [existingHeaderButton, navigationOwnedHeaderLink])
        const root = new Element('navigation-root')

        global.window = {
            matchMedia: () => ({ matches: true, addEventListener() {}, removeEventListener() {} }),
            dispatchEvent() {},
            clearTimeout() {},
            setTimeout(callback) {
                callback()
                return 1
            },
        }
        global.document = {
            body: { classList: { add() {}, remove() {}, toggle() {} } },
            querySelectorAll: () => [existingMain, navigationOwnedMain, navigationOwnedFooter],
            addEventListener() {},
        }
        global.CustomEvent = class {
            constructor(name, options) {
                this.name = name
                this.detail = options.detail
            }
        }

        eval(source)

        const menu = window.capellHeaderNavigation()
        menu.\$el = root
        menu.mobileMenuMediaQuery = { matches: true }

        menu.setPageInert(true)

        assert.equal(existingMain.hasAttribute('data-capell-navigation-inert'), false)
        assert.equal(existingMain.hasAttribute('data-capell-navigation-aria-hidden'), false)
        assert.equal(navigationOwnedMain.getAttribute('data-capell-navigation-inert'), 'true')
        assert.equal(navigationOwnedMain.getAttribute('data-capell-navigation-aria-hidden'), 'true')
        assert.equal(navigationOwnedFooter.getAttribute('data-capell-navigation-inert'), 'true')
        assert.equal(existingHeaderButton.hasAttribute('data-capell-navigation-inert'), false)
        assert.equal(navigationOwnedHeaderLink.getAttribute('data-capell-navigation-inert'), 'true')

        menu.setPageInert(false)

        assert.equal(existingMain.hasAttribute('inert'), true)
        assert.equal(existingMain.getAttribute('aria-hidden'), 'true')
        assert.equal(navigationOwnedMain.hasAttribute('inert'), false)
        assert.equal(navigationOwnedMain.hasAttribute('aria-hidden'), false)
        assert.equal(navigationOwnedFooter.hasAttribute('inert'), false)
        assert.equal(existingHeaderButton.hasAttribute('inert'), true)
        assert.equal(existingHeaderButton.getAttribute('aria-hidden'), 'true')
        assert.equal(navigationOwnedHeaderLink.hasAttribute('inert'), false)

        menu.mobileMenuMediaQuery = { matches: false }
        menu.setPageInert(true)

        assert.equal(navigationOwnedMain.hasAttribute('inert'), false)
        assert.equal(navigationOwnedHeaderLink.hasAttribute('inert'), false)
    JS;

    $process = new Process(['node']);
    $process->setInput($nodeScript);
    $process->setTimeout(10);
    $process->run();

    if (! $process->isSuccessful()) {
        throw new RuntimeException($process->getErrorOutput() . $process->getOutput());
    }

    expect($process->isSuccessful())->toBeTrue();
});

function navigationFileContents(string $path): string
{
    $contents = file_get_contents($path);

    if (! is_string($contents)) {
        throw new RuntimeException(sprintf('Expected %s to be readable.', $path));
    }

    return $contents;
}

it('registers frontend render hooks once per registry instance', function (): void {
    $provider = new NavigationServiceProvider(app());
    $registerHooks = new ReflectionMethod($provider, 'registerFrontendRenderHooksForRegistry');

    $firstRegistry = new RenderHookRegistry;
    $secondRegistry = new RenderHookRegistry;

    $registerHooks->invoke($provider, $firstRegistry);
    $registerHooks->invoke($provider, $firstRegistry);
    $registerHooks->invoke($provider, $secondRegistry);

    expect($firstRegistry->get(RenderHookLocation::HeaderAfter))->toHaveCount(2)
        ->and($secondRegistry->get(RenderHookLocation::HeaderAfter))->toHaveCount(2);
});

it('defers frontend render hook registration until navigation is installed', function (): void {
    $provider = new NavigationServiceProvider(app());
    $registerHooks = new ReflectionMethod($provider, 'registerFrontendRenderHooksForRegistry');

    CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName, false);

    $notInstalledRegistry = new RenderHookRegistry;
    $registerHooks->invoke($provider, $notInstalledRegistry);

    CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);

    $installedRegistry = new RenderHookRegistry;
    $registerHooks->invoke($provider, $installedRegistry);

    expect($notInstalledRegistry->get(RenderHookLocation::HeaderAfter))->toBeEmpty()
        ->and($installedRegistry->get(RenderHookLocation::HeaderAfter))->toHaveCount(2);
});
