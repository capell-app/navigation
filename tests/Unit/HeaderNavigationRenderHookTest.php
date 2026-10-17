<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Theme;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\FrontendContext;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\HeaderNavigationBreakpoint;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Navigation\Support\NavigationFrontendRuntimeManifestContributor;
use Capell\Tests\Support\RenderedHtml;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;
use Symfony\Component\Process\Process;

function navigationRenderedMenu(HeaderNavigationBreakpoint $breakpoint = HeaderNavigationBreakpoint::Lg): string
{
    Blaze::disable();
    $context = new FrontendContext(null, null, null, null, new Theme(['meta' => ['dark_mode_toggle' => false]]), [], null);
    $context->setFrontendData('runtimeManifest', FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::FullLivewire));
    app()->instance(FrontendContextReader::class, $context);
    Frontend::clearResolvedInstance(FrontendContextReader::class);
    $menu = new NavigationRenderData(null, 'main', 'Main', 'menu', collect([
        new NavigationItemRenderData('Home', NavigationItemType::Link, '/home', true, collect(), ['url' => '/home']),
    ]));
    $context->setFrontendData(NavigationFrontendRuntimeManifestContributor::renderModelKey('main'), $menu);
    View::share('menu', $menu);
    View::share('navigation', null);

    return view('capell-navigation::components.header.navigation', [
        'menu' => $menu, 'itemClass' => '', 'breakpoint' => $breakpoint,
    ])->render();
}

it('returns the supported default and foundation responsive boundaries', function (): void {
    expect(HeaderNavigationBreakpoint::cases())->toBe([HeaderNavigationBreakpoint::Lg, HeaderNavigationBreakpoint::Xl])
        ->and(HeaderNavigationBreakpoint::Lg->mobileMediaQuery())->toBe('(max-width: 1023px)')
        ->and(HeaderNavigationBreakpoint::Xl->mobileMediaQuery())->toBe('(max-width: 1279px)');
});

it('renders both registered header scenarios with independent responsive menus', function (): void {
    navigationRenderedMenu();
    $registry = new RenderHookRegistry;
    app()->instance(RenderHookRegistry::class, $registry);
    (new NavigationServiceProvider(app()))->registerInstalledPackage();

    foreach ([
        'frontend-default-primary-navigation' => 'lg',
        'theme-foundation-primary-navigation' => 'xl',
    ] as $scenario => $breakpoint) {
        $html = $registry->renderAll(RenderHookLocation::HeaderAfter, scenario: $scenario, target: 'capell::header.index');
        $xpath = (new RenderedHtml($html));
        expect($xpath->count('//a[@href="/home"]'))->toBe(1)
            ->and($xpath->count('//button[@aria-controls="main-menu"]'))->toBe(1);
    }
    expect($registry->renderAll(RenderHookLocation::HeaderAfter, scenario: 'unrelated', target: 'capell::header.index'))->toBe('');
});

it('renders a labelled menu toggle whose controlled navigation exists', function (): void {
    $html = navigationRenderedMenu();
    $xpath = (new RenderedHtml($html));
    $button = $xpath->element('//button[@aria-controls]');
    expect($button->getAttribute('aria-label'))->toBe(__('capell-frontend::generic.open_menu'))
        ->and($xpath->count('//*[@id="' . $button->getAttribute('aria-controls') . '"]'))->toBe(1);
});

it('renders the active item and its icon under the active link', function (): void {
    $item = new NavigationItemData(label: 'Home', data: ['url' => '/home', 'icon' => 'heroicon-o-home'], active: true);
    $html = view('capell-navigation::components.header.menu.item', ['item' => $item, 'itemClass' => ''])->render();
    $xpath = (new RenderedHtml($html));
    $link = $xpath->element('//a[@href="/home"]');
    expect(explode(' ', $link->getAttribute('class')))->toContain('active')
        ->and($xpath->count('//a[@href="/home"]//*[local-name()="svg"]'))->toBe(1);
});

it('only releases mobile menu inert attributes owned by navigation', function (HeaderNavigationBreakpoint $breakpoint): void {
    $nodeVersion = new Process(['node', '--version']);
    $nodeVersion->run();

    if (! $nodeVersion->isSuccessful()) {
        $this->markTestSkipped('Node.js is required to run the navigation behavior test.');
    }

    $html = (new RenderedHtml(navigationRenderedMenu($breakpoint)));
    $navigationScript = base64_encode($html->element('//script')->textContent);
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

        assert.equal(existingMain.hasAttribute('data-capell-interaction-navigation-inert'), false)
        assert.equal(existingMain.hasAttribute('data-capell-interaction-navigation-aria-hidden'), false)
        assert.equal(navigationOwnedMain.getAttribute('data-capell-interaction-navigation-inert'), 'true')
        assert.equal(navigationOwnedMain.getAttribute('data-capell-interaction-navigation-aria-hidden'), 'true')
        assert.equal(navigationOwnedFooter.getAttribute('data-capell-interaction-navigation-inert'), 'true')
        assert.equal(existingHeaderButton.hasAttribute('data-capell-interaction-navigation-inert'), false)
        assert.equal(navigationOwnedHeaderLink.getAttribute('data-capell-interaction-navigation-inert'), 'true')

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
})->with([
    'default lg breakpoint' => HeaderNavigationBreakpoint::Lg,
    'foundation xl breakpoint' => HeaderNavigationBreakpoint::Xl,
]);

it('registers each header scenario once when a provider or registry is resolved repeatedly', function (): void {
    navigationRenderedMenu();
    foreach ([new RenderHookRegistry, new RenderHookRegistry] as $registry) {
        app()->instance(RenderHookRegistry::class, $registry);
        foreach ([new NavigationServiceProvider(app()), new NavigationServiceProvider(app())] as $provider) {
            $provider->registerInstalledPackage();
        }
        $html = $registry->renderAll(RenderHookLocation::HeaderAfter, scenario: 'frontend-default-primary-navigation', target: 'capell::header.index');
        expect((new RenderedHtml($html))->count('//a[@href="/home"]'))->toBe(1);
    }
});

it('defers rendered navigation until the package is installed', function (): void {
    navigationRenderedMenu();
    CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName, false);
    $registry = new RenderHookRegistry;
    app()->instance(RenderHookRegistry::class, $registry);
    $provider = new NavigationServiceProvider(app());
    $provider->register();
    $provider->boot();
    expect($registry->renderAll(RenderHookLocation::HeaderAfter, scenario: 'frontend-default-primary-navigation', target: 'capell::header.index'))->toBe('');

    CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);
    $provider->registerInstalledPackage();
    $html = $registry->renderAll(RenderHookLocation::HeaderAfter, scenario: 'frontend-default-primary-navigation', target: 'capell::header.index');
    expect((new RenderedHtml($html))->count('//a[@href="/home"]'))->toBe(1);
});
