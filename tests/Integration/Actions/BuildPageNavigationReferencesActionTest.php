<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildPageNavigationReferencesAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('returns only same-site navigations that reference the page morph', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $page = Page::factory()->site($site)->create();

    $matchingNavigation = Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create(['name' => 'Matching']);

    Navigation::factory()
        ->site($otherSite)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create(['name' => 'Other site']);

    Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => 'other-morph',
                ],
                'children' => [],
            ],
        ])
        ->create(['name' => 'Other morph']);

    $references = BuildPageNavigationReferencesAction::run($page);

    expect($references)->toHaveCount(1)
        ->and($references->first()?->is($matchingNavigation))->toBeTrue();
});

it('finds nested page navigation references', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->site($site)->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/parent'],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $page->getKey(),
                            'pageable_type' => $page->getMorphClass(),
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ])
        ->create();

    $references = BuildPageNavigationReferencesAction::run($page);

    expect($references)->toHaveCount(1)
        ->and($references->first()?->is($navigation))->toBeTrue();
});

it('includes global navigations that reference the page morph', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->site($site)->create();

    $navigation = Navigation::factory()
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create([
            'name' => 'Global',
            'site_id' => null,
        ]);

    $references = BuildPageNavigationReferencesAction::run($page);

    expect($references)->toHaveCount(1)
        ->and($references->first()?->is($navigation))->toBeTrue();
});

it('uses the indexed page references table instead of scanning navigation item json', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->site($site)->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create();

    expect(DB::table('navigation_page_references')
        ->where('navigation_id', $navigation->getKey())
        ->where('pageable_type', $page->getMorphClass())
        ->where('pageable_id', $page->getKey())
        ->exists())->toBeTrue();

    DB::enableQueryLog();

    $references = BuildPageNavigationReferencesAction::run($page);

    expect($references)->toHaveCount(1)
        ->and($references->first()?->is($navigation))->toBeTrue()
        ->and(navigationReferenceQueriesUsingItemsLike())->toBe([]);
});

it('memoizes page navigation references for repeated form renders in one request', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->site($site)->create();

    Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create();

    app()->instance('request', Request::create('/admin/pages/' . $page->getKey() . '/edit'));
    DB::enableQueryLog();

    $firstReferences = BuildPageNavigationReferencesAction::run($page);
    $navigationQueryCountAfterFirstRun = navigationReferenceLookupQueryCount();
    expect(request()->attributes->has('capell.navigation.page_references'))->toBeTrue();
    $secondReferences = BuildPageNavigationReferencesAction::run($page);

    expect($firstReferences)->toHaveCount(1)
        ->and($secondReferences)->toHaveCount(1)
        ->and(navigationReferenceLookupQueries())->toHaveCount($navigationQueryCountAfterFirstRun);
});

it('refreshes indexed references when navigation items change', function (): void {
    $site = Site::factory()->create();
    $firstPage = Page::factory()->site($site)->create();
    $secondPage = Page::factory()->site($site)->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $firstPage->getKey(),
                    'pageable_type' => $firstPage->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create();

    expect(BuildPageNavigationReferencesAction::run($firstPage))->toHaveCount(1);

    $navigation->update([
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $secondPage->getKey(),
                    'pageable_type' => $secondPage->getMorphClass(),
                ],
                'children' => [],
            ],
        ],
    ]);

    expect(BuildPageNavigationReferencesAction::run($firstPage))->toHaveCount(0)
        ->and(BuildPageNavigationReferencesAction::run($secondPage))->toHaveCount(1)
        ->and(DB::table('navigation_page_references')->where('navigation_id', $navigation->getKey())->count())->toBe(1);
});

/**
 * @return list<string>
 */
function navigationReferenceQueriesUsingItemsLike(): array
{
    return array_values(collect(DB::getQueryLog())
        ->map(static fn (array $query): string => (string) ($query['query'] ?? ''))
        ->filter(static fn (string $query): bool => str_contains($query, '"items" like') || str_contains($query, '`items` like'))
        ->values()
        ->all());
}

function navigationReferenceLookupQueryCount(): int
{
    return count(navigationReferenceLookupQueries());
}

/**
 * @return list<string>
 */
function navigationReferenceLookupQueries(): array
{
    return array_values(collect(DB::getQueryLog())
        ->map(static fn (array $query): string => (string) ($query['query'] ?? ''))
        ->filter(static fn (string $query): bool => str_contains($query, 'navigation_page_references') || str_contains($query, 'from "navigations"') || str_contains($query, 'from `navigations`'))
        ->values()
        ->all());
}
