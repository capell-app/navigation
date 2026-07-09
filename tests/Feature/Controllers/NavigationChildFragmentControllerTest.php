<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Enums\NavigationChildrenLoadingEnum;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationCacheKeys;
use Illuminate\Support\Collection;

it('returns a lazy navigation child fragment for a valid public payload', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $parentPage = Page::factory()->site($site)->withTranslations()->create();
    $currentPage = Page::factory()->site($site)->withTranslations()->parent($parentPage)->create();
    $siteDomain = $site->siteDomains->first();

    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'key' => 'parent',
                'label' => 'Parent',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/parent',
                    'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
                ],
                'children' => [
                    [
                        'key' => 'current',
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $currentPage->getKey(),
                            'pageable_type' => $currentPage->getMorphClass(),
                        ],
                    ],
                ],
            ],
        ],
    ])->refresh();

    $renderModel = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: $siteDomain,
    ));

    $url = navigationChildFragmentUrl(navigationChildFragmentFirstItem($renderModel->items)->lazyFragmentUrl);

    expect($url)->toBeString();

    $this->get($url)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee($currentPage->translation->label)
        ->assertSee('is-active');
});

it('can repeatedly load the same lazy mega menu fragment', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'key' => 'mega-menu',
                'label' => 'Solutions',
                'type' => NavigationItemType::Link->value,
                'data' => [
                    'url' => '/solutions',
                    'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
                ],
                'children' => [
                    [
                        'key' => 'platform',
                        'label' => 'Platform',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/solutions/platform'],
                    ],
                    [
                        'key' => 'services',
                        'label' => 'Services',
                        'type' => NavigationItemType::Link->value,
                        'data' => ['url' => '/solutions/services'],
                    ],
                ],
            ],
        ],
    ]);

    $view = $this->blade(
        '<x-capell-navigation::menu key="main" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    preg_match('/data-navigation-fragment-url="([^"]+)"/', (string) $view, $matches);

    $url = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5);

    expect($url)->toStartWith('http://localhost/_capell/navigation/children?payload=');

    $firstResponse = $this->get($url)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex')
        ->assertSee('Platform')
        ->assertSee('Services')
        ->assertDontSee('data-navigation-lazy-fragment');

    $firstContent = $firstResponse->getContent();

    foreach (range(1, 3) as $loadAttempt) {
        $this->get($url)
            ->assertSuccessful()
            ->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('Platform')
            ->assertSee('Services')
            ->assertContent($firstContent);
    }
});

it('returns not found for an invalid lazy navigation fragment payload', function (): void {
    $this->get(route('capell-navigation.children', ['payload' => 'invalid']))
        ->assertNotFound();
});

it('builds lazy fragment cache keys outside the cache enum', function (): void {
    expect(NavigationCacheKeys::lazyFragmentKey('main|item'))
        ->toBe(NavigationCacheEnum::LazyFragments->value . '-' . hash('sha256', 'main|item'));
});

/**
 * @param  Collection<int, NavigationItemRenderData>  $items
 */
function navigationChildFragmentFirstItem(Collection $items): NavigationItemRenderData
{
    $item = $items->first();

    throw_unless($item instanceof NavigationItemRenderData, RuntimeException::class, 'Expected a navigation render item.');

    return $item;
}

function navigationChildFragmentUrl(?string $url): string
{
    throw_unless(is_string($url) && $url !== '', RuntimeException::class, 'Expected a navigation child fragment URL.');

    return $url;
}
