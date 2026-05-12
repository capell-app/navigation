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

use function Pest\Laravel\get;

uses(TestingFrontend::class);

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

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSee('id="main-menu"', false)
        ->assertSee('Docs')
        ->assertSee('aria-controls="main-menu"', false)
        ->assertSee('Alpine.data(\'capellHeaderNavigation\'', false)
        ->assertDontSee('id="header"', false);
});

test('foundation theme displays the main navigation menu', function (): void {
    registerFoundationThemeComponentsForRendering();

    $theme = Theme::factory()
        ->defaultMeta()
        ->state([
            'key' => 'foundation-rendering-test',
            'meta' => [
                ...Theme::factory()->defaultMeta()->make()->meta,
                'footer' => false,
                'header_file' => 'capell-foundation-test::header.index',
            ],
        ])
        ->create();

    [$page] = createFrontendPageWithMainNavigation($theme);

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSee('id="main-menu"', false)
        ->assertSee('Docs')
        ->assertSee('aria-controls="main-menu"', false)
        ->assertSee('Alpine.data(\'capellHeaderNavigation\'', false)
        ->assertSee('id="header"', false);
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

function registerFoundationThemeComponentsForRendering(): void
{
    $foundationThemePath = realpath(dirname(__DIR__, 5) . '/foundation-theme');

    expect($foundationThemePath)->not->toBeFalse();

    Blade::anonymousComponentPath($foundationThemePath . '/resources/views/components', 'capell-foundation-test');
    app('view')->addNamespace('capell-foundation-test', $foundationThemePath . '/resources/views');
    Blade::component('capell-foundation-test::components.header.index', 'capell-foundation-test::header.index');
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
