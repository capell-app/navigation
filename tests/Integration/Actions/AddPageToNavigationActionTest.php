<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Spatie\LaravelData\DataCollection;

it('adds a page to navigation structure', function (): void {
    $page = Page::factory()->create();
    $navigation = Navigation::factory()->defaultItems(3)->create();

    AddPageToNavigationAction::run($page, $navigation);

    $navigation->refresh();

    expect($navigation->items)->toBeInstanceOf(DataCollection::class)->toHaveCount(4);
});

it('adds a page to an empty navigation structure', function (): void {
    $page = Page::factory()->create();
    $navigation = Navigation::factory()->create([
        'items' => null,
    ]);

    AddPageToNavigationAction::run($page, $navigation);

    $navigation->refresh();

    expect($navigation->items)->toBeInstanceOf(DataCollection::class)->toHaveCount(1);
});

it('does not duplicate a page that already exists as a child item', function (): void {
    $parentPage = Page::factory()->create();
    $childPage = Page::factory()->create();
    $navigation = Navigation::factory()->create([
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $parentPage->getKey(),
                    'pageable_type' => $parentPage->getMorphClass(),
                ],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'data' => [
                            'pageable_id' => $childPage->getKey(),
                            'pageable_type' => $childPage->getMorphClass(),
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ]);

    AddPageToNavigationAction::run($childPage, $navigation);

    $navigation->refresh();

    expect($navigation->items)->toBeInstanceOf(DataCollection::class)->toHaveCount(1);
});

it('ignores a stale navigation model that no longer exists', function (): void {
    $page = Page::factory()->create();
    $navigation = Navigation::factory()->create([
        'items' => null,
    ]);
    $navigationId = $navigation->getKey();

    $navigation->delete();

    AddPageToNavigationAction::run($page, $navigation);

    expect(Navigation::query()->whereKey($navigationId)->exists())->toBeFalse();
});
