<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @param  Collection<int, NavigationItemData>  $items
 * @return list<string|null>
 */
function navigationAutoChildLabels(Collection $items, int $index): array
{
    $item = $items->get($index);

    throw_unless($item instanceof NavigationItemData, RuntimeException::class, 'Expected navigation item at index ' . $index . '.');

    $labels = [];

    foreach ($item->children?->all() ?? [] as $child) {
        throw_unless($child instanceof NavigationItemData, RuntimeException::class, 'Expected child navigation items.');
        $labels[] = $child->label;
    }

    return $labels;
}

/**
 * @return array{site: Site, language: Language, parents: list<Page>}
 */
function navigationAutoChildrenFixture(): array
{
    NavigationItemsLoader::flushPageCache();

    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])->create();
    $first = Page::factory()->site($site)->withTranslations()->create();
    $second = Page::factory()->site($site)->withTranslations()->create();

    foreach (['First child A', 'First child B'] as $name) {
        Page::factory()->site($site)->withTranslations(data: ['label' => $name])->parent($first)->state(['name' => $name])->create();
    }

    Page::factory()->site($site)->withTranslations(data: ['label' => 'Second child'])->parent($second)->state(['name' => 'Second child'])->create();

    return ['site' => $site, 'language' => $language, 'parents' => [$first, $second]];
}

function navigationAutoChildrenDomain(Site $site): SiteDomain
{
    $domain = $site->siteDomains->first();

    throw_unless($domain instanceof SiteDomain, RuntimeException::class, 'Expected a site domain.');

    return $domain;
}

/**
 * @param  list<Page>  $parents
 */
function navigationAutoChildrenMenu(Site $site, Language $language, array $parents): Navigation
{
    return Navigation::factory()->make([
        'key' => 'main',
        'site_id' => $site->id,
        'language_id' => $language->id,
        'items' => array_map(static fn (Page $parent): array => [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_id' => $parent->id,
                'pageable_type' => $parent->getMorphClass(),
                'auto_children' => true,
            ],
        ], $parents),
    ]);
}

it('adds automatic children while the visitor is on a different page model', function (): void {
    ['site' => $site, 'language' => $language, 'parents' => $parents] = navigationAutoChildrenFixture();

    $currentPage = new class extends Page
    {
        public function getMorphClass(): string
        {
            return 'navigation-test-landing-page';
        }

        public function getTable(): string
        {
            return (new Page)->getTable();
        }
    };
    $source = Page::factory()->site($site)->withTranslations()->create();
    $currentPage->setRawAttributes($source->getAttributes(), true);
    $currentPage->exists = true;

    $items = (new NavigationItemsLoader(
        navigation: navigationAutoChildrenMenu($site, $language, $parents),
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: navigationAutoChildrenDomain($site),
    ))->load();

    expect(navigationAutoChildLabels($items, 0))->toEqualCanonicalizing(['First child A', 'First child B'])
        ->and(navigationAutoChildLabels($items, 1))->toBe(['Second child']);
});

it('lists the automatic children of every menu item in one page listing', function (): void {
    ['site' => $site, 'language' => $language, 'parents' => $parents] = navigationAutoChildrenFixture();
    $currentPage = Page::factory()->site($site)->withTranslations()->create();

    $parentIds = array_map(static fn (Page $parent): int => $parent->id, $parents);
    $childListings = 0;
    DB::listen(function (QueryExecuted $query) use ($parentIds, &$childListings): void {
        $bindings = array_values(array_filter(array_map(
            static fn (mixed $binding): ?int => is_numeric($binding) ? (int) $binding : null,
            $query->bindings,
        ), is_int(...)));
        $listsPageIds = preg_match('/^select [`"]?pages[`"]?\.[`"]?id[`"]? from/i', $query->sql) === 1;

        if ($listsPageIds && str_contains($query->sql, 'parent_id') && array_intersect($parentIds, $bindings) !== []) {
            $childListings++;
        }
    });

    $items = (new NavigationItemsLoader(
        navigation: navigationAutoChildrenMenu($site, $language, $parents),
        page: $currentPage,
        site: $site,
        language: $language,
        siteDomain: navigationAutoChildrenDomain($site),
    ))->load();

    expect($childListings)->toBe(1)
        ->and(navigationAutoChildLabels($items, 0))->toEqualCanonicalizing(['First child A', 'First child B'])
        ->and(navigationAutoChildLabels($items, 1))->toBe(['Second child']);
});
