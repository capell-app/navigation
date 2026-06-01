<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

/**
 * @param  Collection<int, NavigationItemData>  $items
 */
function navigationLoaderItem(Collection $items, int $index): NavigationItemData
{
    $item = $items->get($index);

    throw_unless($item instanceof NavigationItemData, RuntimeException::class, 'Expected navigation item at index ' . $index . '.');

    return $item;
}

function navigationLoaderChild(NavigationItemData $item, int $index): NavigationItemData
{
    $children = $item->children;

    throw_unless($children instanceof DataCollection, RuntimeException::class, 'Expected navigation item children.');

    $child = $children[$index] ?? null;

    throw_unless($child instanceof NavigationItemData, RuntimeException::class, 'Expected child navigation item at index ' . $index . '.');

    return $child;
}

function navigationLoaderPageUrl(Page $page): string
{
    $pageUrl = $page->pageUrl;

    throw_if($pageUrl === null, RuntimeException::class, 'Expected page URL for navigation loader assertion.');

    return $pageUrl->full_url;
}

it('loads morph navigation items and preserves order', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $secondaryPage = Page::factory()->site($site)->withTranslations()->create();
    $nestedPage = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
    ]);

    $navigation->items = [
        [
            'label' => 'External',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => 'http://example.com'],
        ],
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $secondaryPage->id,
                'pageable_type' => $secondaryPage->getMorphClass(),
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
                        'pageable_id' => $currentPage->id,
                        'pageable_type' => $currentPage->getMorphClass(),
                    ],
                ],
            ],
        ],
    ];

    $domain = $site->siteDomains->first();

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $domain,
    );

    $items = $loader->fetchMenuItems();

    $loader->activeMenuItems($items);

    expect($items)->toHaveCount(3)
        ->and(navigationLoaderItem($items, 0)->type->value)->toBe(NavigationItemType::Link->value)
        ->and(navigationLoaderItem($items, 1)->type->value)->toBe(NavigationItemType::Page->value)
        ->and(navigationLoaderItem($items, 1)->data['pageable_id'])->toBe($secondaryPage->id)
        ->and(navigationLoaderItem($items, 1)->data['url'])->toBe(navigationLoaderPageUrl($secondaryPage))
        ->and(navigationLoaderChild(navigationLoaderItem($items, 2), 0)->data['pageable_id'])->toBe($nestedPage->id)
        ->and(navigationLoaderChild(navigationLoaderItem($items, 2), 0)->data['url'])->toBe(navigationLoaderPageUrl($nestedPage))
        ->and(navigationLoaderChild(navigationLoaderItem($items, 2), 1)->data['pageable_id'])->toBe($currentPage->id)
        ->and(navigationLoaderChild(navigationLoaderItem($items, 2), 1)->active)->toBeTrue()
        ->and(navigationLoaderItem($items, 2)->active)->toBeTrue();
});

it('loads pure link navigation items', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/docs'],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(1)
        ->and(navigationLoaderItem($items, 0)->type->value)->toBe(NavigationItemType::Link->value)
        ->and(navigationLoaderItem($items, 0)->data['url'])->toBe('/docs');
});

it('skips page items that belong to a different site', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $otherSite = Site::factory()->language($language)->withTranslations()->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $otherSitePage = Page::factory()->site($otherSite)->withTranslations()->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $otherSitePage->id,
                    'pageable_type' => $otherSitePage->getMorphClass(),
                ],
            ],
            [
                'label' => 'Safe Link',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/safe'],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(1)
        ->and(navigationLoaderItem($items, 0)->label)->toBe('Safe Link');
});

it('excludes hidden navigation items and hidden nested children', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();

    $navigation = Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $site->language->id,
        'items' => [
            [
                'label' => 'Visible',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/visible'],
            ],
            [
                'label' => 'Hidden',
                'type' => NavigationItemType::Link->value,
                'is_visible' => false,
                'data' => ['url' => '/hidden'],
                'children' => [
                    [
                        'label' => 'Hidden Child Subtree',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/hidden-child-subtree'],
                    ],
                ],
            ],
            [
                'label' => 'Visible Parent',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/visible-parent'],
                'children' => [
                    [
                        'label' => 'Nested Hidden',
                        'type' => NavigationItemType::Link->value,
                        'is_visible' => false,
                        'data' => ['url' => '/nested-hidden'],
                    ],
                    [
                        'label' => 'Nested Visible',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/nested-visible'],
                    ],
                ],
            ],
        ],
    ]);

    $loader = new NavigationItemsLoader(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $site->language,
        siteDomain: $site->siteDomains->first(),
    );

    $items = $loader->fetchMenuItems();

    expect($items)->toHaveCount(2)
        ->and($items->pluck('label')->all())->toBe(['Visible', 'Visible Parent'])
        ->and(navigationLoaderItem($items, 1)->children)->toHaveCount(1)
        ->and(navigationLoaderChild(navigationLoaderItem($items, 1), 0)->label)->toBe('Nested Visible');
});
