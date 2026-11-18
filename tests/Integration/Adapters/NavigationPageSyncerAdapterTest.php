<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\TableName;
use Capell\Navigation\Contracts\NavigationPageSyncer;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('is bound in the container when the navigation package is loaded', function (): void {
    expect(app()->bound(NavigationPageSyncer::class))->toBeTrue();
});

it('removes a page from all navigations when the page is deleted via the syncer', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->id]);

    $navigation = Navigation::factory()->create([
        'site_id' => $site->id,
        'items' => [
            (string) Str::uuid() => [
                'label' => 'Page link',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $site->id,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ],
    ]);

    resolve(NavigationPageSyncer::class)->removePageFromAllNavigations($page);

    $navigation->refresh();

    $containsPage = capell_test_collect($navigation->items)->contains(
        fn (mixed $item): bool => isset($item->data['pageable_id'])
            && (int) $item->data['pageable_id'] === $page->getKey(),
    );

    expect($containsPage)->toBeFalse();
});

it('removes a permanently deleted page from every menu and drops its page references', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->id]);
    $kept = Page::factory()->create(['site_id' => $site->id]);
    $pageItem = static fn (Page $target): array => [
        'label' => 'Page link',
        'type' => NavigationItemType::Page->value,
        'data' => ['pageable_id' => $target->getKey(), 'pageable_type' => $target->getMorphClass()],
        'children' => [],
    ];

    $navigation = Navigation::factory()->create(['site_id' => $site->id, 'items' => [$pageItem($page), $pageItem($kept)]]);
    $binned = Navigation::factory()->create(['site_id' => $site->id, 'items' => [$pageItem($page)]]);
    $binned->delete();

    $references = static fn (Page $target): int => DB::table(TableName::resolve('navigation_page_references'))
        ->where('pageable_type', $target->getMorphClass())
        ->where('pageable_id', $target->getKey())
        ->count();

    expect($references($page))->toBe(2);

    $page->forceDelete();

    $itemPageIds = static fn (Navigation $menu): array => capell_test_collect(Navigation::withTrashed()->whereKey($menu->getKey())->firstOrFail()->items)
        ->map(static fn (mixed $item): mixed => data_get($item, 'data.pageable_id'))
        ->all();

    expect($itemPageIds($navigation))->toBe([$kept->getKey()])
        ->and($itemPageIds($binned))->toBe([])
        ->and($references($page))->toBe(0)
        ->and($references($kept))->toBe(1);
});

it('keeps menu items for a page in the bin so restoring the page restores its menu links', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->id]);
    $navigation = Navigation::factory()->create(['site_id' => $site->id, 'items' => [[
        'label' => 'Page link',
        'type' => NavigationItemType::Page->value,
        'data' => ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()],
        'children' => [],
    ]]]);

    $page->delete();

    expect(capell_test_collect($navigation->refresh()->items))->toHaveCount(1);
});
