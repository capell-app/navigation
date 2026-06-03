<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\TestingFrontend;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\View\DynamicComponent;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

function navigationFeaturePageUrl(Page $page): string
{
    $pageUrl = $page->pageUrl;

    throw_if($pageUrl === null, RuntimeException::class, 'Expected page URL for navigation feature assertion.');

    return $pageUrl->full_url;
}

beforeEach(function (): void {
    registerThemedHeaderComponentForRendering();
});

test('frontend default theme displays the main navigation menu', function (): void {
    $theme = Theme::factory()
        ->defaultMeta()
        ->state([
            'key' => 'frontend-fallback-default',
            'meta' => [
                ...Theme::factory()->defaultMeta()->make()->meta,
                'footer' => false,
                'header_file' => 'capell::header.index',
            ],
        ])
        ->create();

    [$page] = createFrontendPageWithMainNavigation($theme);

    get(navigationFeaturePageUrl($page))
        ->assertOk()
        ->assertElementExists('#main-menu[aria-label="Main navigation"]')
        ->assertSee('Docs')
        ->assertElementExists('[aria-controls="main-menu"]')
        ->assertSee("Alpine.data('capellHeaderNavigation'", false)
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('#header'));
});

test('themed header displays the main navigation menu', function (): void {
    $theme = Theme::factory()
        ->defaultMeta()
        ->state([
            'key' => 'foundation-rendering-test',
            'meta' => [
                ...Theme::factory()->defaultMeta()->make()->meta,
                'footer' => false,
                'header_file' => 'capell-navigation-test::header',
            ],
        ])
        ->create();

    [$page] = createFrontendPageWithMainNavigation($theme);

    get(navigationFeaturePageUrl($page))
        ->assertOk()
        ->assertElementExists('#main-menu[aria-label="Main navigation"]')
        ->assertSee('Docs')
        ->assertElementExists('[aria-controls="main-menu"]')
        ->assertSee("Alpine.data('capellHeaderNavigation'", false)
        ->assertElementExists('#header');
});

test('page renders without error when navigation exists with main handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Main,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('anonymous frontend menu output leaks no admin internals', function (): void {
    $theme = Theme::factory()
        ->defaultMeta()
        ->state([
            'key' => 'foundation-public-safety-test',
            'meta' => [
                ...Theme::factory()->defaultMeta()->make()->meta,
                'footer' => false,
                'header_file' => 'capell-navigation-test::header',
            ],
        ])
        ->create();

    [$page, $site] = createFrontendPageWithMainNavigation($theme);

    expect(auth()->check())->toBeFalse();

    $response = get(navigationFeaturePageUrl($page))->assertOk();

    $response
        ->assertSee('Docs')
        ->assertDontSee('pageable_id', false)
        ->assertDontSee('pageable_type', false)
        ->assertDontSee('is_visible', false)
        ->assertDontSee('Capell\\Core\\Models\\Page', false)
        ->assertDontSee('"id":' . $page->id, false)
        ->assertDontSee('/admin/', false)
        ->assertDontSee('NavigationResource', false);
});

/**
 * @return array{0: Page, 1: Site}
 */
function createFrontendPageWithMainNavigation(Theme $theme): array
{
    $site = Site::factory()
        ->theme($theme)
        ->withTranslations()
        ->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Main,
            'items' => [
                [
                    'label' => 'Docs',
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'label' => 'Current',
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    return [$page, $site];
}

function registerThemedHeaderComponentForRendering(): void
{
    Blade::componentNamespace('Capell\\Navigation\\Tests\\Fixtures\\View\\Components', 'capell-navigation-test');
    View::addNamespace('capell-navigation-test', __DIR__ . '/../../../Fixtures/views');

    clearDynamicComponentResolverCache();
}

function clearDynamicComponentResolverCache(): void
{
    $dynamicComponent = new ReflectionClass(DynamicComponent::class);

    $compiler = $dynamicComponent->getProperty('compiler');
    $compiler->setValue(null);

    $componentClasses = $dynamicComponent->getProperty('componentClasses');
    $componentClasses->setValue([]);
}

test('page renders without error when navigation exists with footer handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Footer,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('page renders without error when navigation exists with sub-footer handle', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $home = Page::factory()->site($site)->home()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::SubFooter,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $home->id,
                        'pageable_type' => $home->getMorphClass(),
                    ],
                ],
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    get($page->pageUrl->full_url)->assertOk();
});

test('navigation renders auto_children for page item without error', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    Page::factory()->site($site)->withTranslations()->parent($parent)->state(['name' => 'Child 1'])->create();
    Page::factory()->site($site)->withTranslations()->parent($parent)->state(['name' => 'Child 2'])->create();

    $language = $site->languages->first();

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->state([
            'key' => NavigationHandle::Main,
            'items' => [
                [
                    'type' => NavigationItemType::Page,
                    'data' => [
                        'pageable_id' => $parent->id,
                        'pageable_type' => $parent->getMorphClass(),
                        'auto_children' => true,
                    ],
                ],
            ],
        ])
        ->create();

    get($parent->pageUrl->full_url)->assertOk();
});
