<?php

declare(strict_types=1);

use Capell\Core\Events\PageUrlChanged;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

function navigationRenderItem(NavigationRenderData $renderData, int ...$indexes): NavigationItemRenderData
{
    $items = $renderData->items;
    $item = null;

    foreach ($indexes as $index) {
        $item = $items->get($index);

        throw_unless($item instanceof NavigationItemRenderData, RuntimeException::class, 'Expected navigation render item at index ' . $index . '.');

        $items = $item->children;
    }

    throw_unless($item instanceof NavigationItemRenderData, RuntimeException::class, 'Expected navigation render item.');

    return $item;
}

function navigationRenderPageUrl(Page $page): string
{
    $pageUrl = $page->pageUrl;

    throw_if($pageUrl === null, RuntimeException::class, 'Expected page URL for navigation render assertion.');

    return $pageUrl->full_url;
}

it('builds a view-ready render model for current page and child active state', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $homePage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $secondaryPage = Page::factory()->site($site)->withTranslations()->create();
    $nestedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'meta' => ['component' => 'capell::stacked-list'],
        'items' => [
            [
                'label' => 'Home',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $homePage->id,
                    'pageable_type' => $homePage->getMorphClass(),
                ],
            ],
            [
                'label' => 'Parent',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/parent'],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $nestedPage->id,
                            'pageable_type' => $nestedPage->getMorphClass(),
                        ],
                    ],
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $secondaryPage->id,
                            'pageable_type' => $secondaryPage->getMorphClass(),
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $secondaryPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(2)
        ->and($renderModel->navigationKey)->toBe('main')
        ->and($renderModel->listComponent)->toBe('capell::stacked-list')
        ->and(navigationRenderItem($renderModel, 0)->label)->toBe('Home')
        ->and(navigationRenderItem($renderModel, 0)->url)->toBe(navigationRenderPageUrl($homePage))
        ->and(navigationRenderItem($renderModel, 0)->active)->toBeFalse()
        ->and(navigationRenderItem($renderModel, 1)->active)->toBeTrue()
        ->and(navigationRenderItem($renderModel, 1, 0)->url)->toBe(navigationRenderPageUrl($nestedPage))
        ->and(navigationRenderItem($renderModel, 1, 0)->active)->toBeFalse()
        ->and(navigationRenderItem($renderModel, 1, 1)->url)->toBe(navigationRenderPageUrl($secondaryPage))
        ->and(navigationRenderItem($renderModel, 1, 1)->active)->toBeTrue();
});

it('expands auto children using the provided site language and domain context', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'primary.test', 'path' => null])
        ->create();
    $otherSite = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'other.test', 'path' => null])
        ->create();
    $parentPage = Page::factory()->site($site)->withTranslations()->create();
    $currentChildPage = Page::factory()->site($site)->withTranslations()->parent($parentPage)->create();
    $otherChildPage = Page::factory()->site($otherSite)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $parentPage->id,
                    'pageable_type' => $parentPage->getMorphClass(),
                    'auto_children' => true,
                ],
            ],
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $otherChildPage->id,
                    'pageable_type' => $otherChildPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentChildPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(1)
        ->and(navigationRenderItem($renderModel, 0)->url)->toBe(navigationRenderPageUrl($parentPage))
        ->and(navigationRenderItem($renderModel, 0)->active)->toBeTrue()
        ->and(navigationRenderItem($renderModel, 0)->children)->toHaveCount(1)
        ->and(navigationRenderItem($renderModel, 0, 0)->label)->toBe($currentChildPage->translation->label)
        ->and(navigationRenderItem($renderModel, 0, 0)->url)->toBe(navigationRenderPageUrl($currentChildPage))
        ->and(navigationRenderItem($renderModel, 0, 0)->active)->toBeTrue()
        ->and(navigationRenderItem($renderModel, 0, 0)->data)->toHaveKey('url')
        ->and(navigationRenderItem($renderModel, 0, 0)->data)->not->toHaveKey('pageable_id')
        ->and(navigationRenderItem($renderModel, 0, 0)->data)->not->toHaveKey('pageable_type');
});

it('clears the page lookup cache through the render-model action', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    BuildNavigationRenderModelAction::run($context);
    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/changed']);

    $staleRenderModel = BuildNavigationRenderModelAction::run($context);
    BuildNavigationRenderModelAction::flushPageCache();
    $freshRenderModel = BuildNavigationRenderModelAction::run($context);

    expect(navigationRenderItem($staleRenderModel, 0)->url)->toBe(navigationRenderPageUrl($linkedPage))
        ->and(navigationRenderItem($freshRenderModel, 0)->url)->toContain('/changed');
});

it('memoizes navigation render models for the current request', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    $firstRenderModel = BuildNavigationRenderModelAction::run($context);
    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/memoized-change']);
    $secondRenderModel = BuildNavigationRenderModelAction::run($context);

    expect($secondRenderModel)->toBe($firstRenderModel)
        ->and(navigationRenderItem($secondRenderModel, 0)->url)->toBe(navigationRenderPageUrl($linkedPage));
});

it('hydrates shared render models from scalar cache payloads when cache object unserialization is disabled', function (): void {
    config()->set('cache.default', 'array');
    config()->set('cache.stores.array.serialize', true);
    config()->set('cache.serializable_classes', false);
    Cache::purge('array');

    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    $firstRenderModel = BuildNavigationRenderModelAction::run($context);
    request()->attributes->remove('capell.navigation.render_models');

    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/shared-cache-change']);

    $secondRenderModel = BuildNavigationRenderModelAction::run($context);

    expect($secondRenderModel)->not->toBe($firstRenderModel)
        ->and($secondRenderModel)->toBeInstanceOf(NavigationRenderData::class)
        ->and(navigationRenderItem($secondRenderModel, 0)->url)->toBe(navigationRenderPageUrl($linkedPage));
});

it('renders heading items without a url', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'label' => 'Company',
                'type' => NavigationItemType::Heading->value,
                'data' => ['icon' => 'heroicon-o-building-office'],
            ],
            [
                'label' => 'About',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/about'],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect($renderModel->items)->toHaveCount(2)
        ->and(navigationRenderItem($renderModel, 0)->type)->toBe(NavigationItemType::Heading)
        ->and(navigationRenderItem($renderModel, 0)->label)->toBe('Company')
        ->and(navigationRenderItem($renderModel, 0)->url)->toBeNull()
        ->and(navigationRenderItem($renderModel, 0)->active)->toBeFalse()
        ->and(navigationRenderItem($renderModel, 1)->url)->toBe('/about');
});

it('removes unsupported icon component names from public render data', function (): void {
    Blade::component('capell-navigation::components.breadcrumbs', 'navigation-test-icon');

    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'label' => 'External',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => 'https://example.com',
                    'icon' => 'external',
                    'active_icon' => 'also-missing',
                ],
            ],
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/docs',
                    'icon' => 'navigation-test-icon',
                    'active_icon' => 'heroicon-s-book-open',
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect(navigationRenderItem($renderModel, 0)->icon)->toBeNull()
        ->and(navigationRenderItem($renderModel, 0)->activeIcon)->toBeNull()
        ->and(navigationRenderItem($renderModel, 0)->data)->not->toHaveKeys(['icon', 'active_icon'])
        ->and(navigationRenderItem($renderModel, 1)->icon)->toBe('navigation-test-icon')
        ->and(navigationRenderItem($renderModel, 1)->activeIcon)->toBe('heroicon-s-book-open')
        ->and(navigationRenderItem($renderModel, 1)->data)->toMatchArray([
            'icon' => 'navigation-test-icon',
            'active_icon' => 'heroicon-s-book-open',
        ]);
});

it('passes mega-menu render settings through public view data', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'label' => 'Solutions',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/solutions',
                    'dropdown_layout' => 'mega',
                    'mega_columns' => 4,
                    'mega_panel_heading' => 'Explore solutions',
                    'mega_panel_description' => 'Grouped links for larger site menus.',
                    'mega_panel_url' => '/solutions',
                    'pageable_id' => 123,
                ],
                'children' => [
                    [
                        'label' => 'Healthcare',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/solutions/healthcare'],
                    ],
                ],
            ],
        ],
    ]);

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    ));

    expect(navigationRenderItem($renderModel, 0)->data)->toMatchArray([
        'url' => '/solutions',
        'dropdown_layout' => 'mega',
        'mega_columns' => 4,
        'mega_panel_heading' => 'Explore solutions',
        'mega_panel_description' => 'Grouped links for larger site menus.',
        'mega_panel_url' => '/solutions',
    ])
        ->and(navigationRenderItem($renderModel, 0)->data)->not->toHaveKey('pageable_id');
});

it('flushes stale page lookup cache when a page url changed event is received', function (): void {
    if (! class_exists(PageUrlChanged::class)) {
        test()->markTestSkipped('Capell Core does not provide the PageUrlChanged event in this checkout.');
    }

    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $linkedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $linkedPage->id,
                    'pageable_type' => $linkedPage->getMorphClass(),
                ],
            ],
        ],
    ]);

    $context = new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $site->siteDomains->first(),
    );

    BuildNavigationRenderModelAction::run($context);

    DB::table('page_urls')->where('id', $linkedPage->pageUrl->id)->update(['url' => '/event-changed']);

    event(new PageUrlChanged(
        page_url_id: (int) $linkedPage->pageUrl->getKey(),
        page_id: (int) $linkedPage->getKey(),
        site_id: (int) $site->getKey(),
        language_id: (int) $language->getKey(),
        old_url: $linkedPage->pageUrl->url,
        new_url: '/event-changed',
    ));

    $renderModel = BuildNavigationRenderModelAction::run($context);

    expect(navigationRenderItem($renderModel, 0)->url)->toContain('/event-changed');
});
