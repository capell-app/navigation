<?php

declare(strict_types=1);

use Capell\Navigation\Actions\EnsureNavigationItemKeysAction;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Spatie\LaravelData\DataCollection;

it('assigns missing item keys recursively while preserving existing keys', function (): void {
    $items = EnsureNavigationItemKeysAction::run([
        [
            'label' => 'Parent',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '/parent'],
            'children' => [
                [
                    'key' => 'existing-child',
                    'label' => 'Child',
                    'type' => NavigationItemType::Link->value,
                    'data' => ['url' => '/parent/child'],
                ],
            ],
        ],
    ]);

    $parent = navigationItemKeyTestFirstItem($items);

    expect($items)->toBeInstanceOf(DataCollection::class)
        ->and($parent)->toBeInstanceOf(NavigationItemData::class)
        ->and($parent->key)->toBeString()
        ->and($parent->key)->not->toBe('')
        ->and(navigationItemKeyTestFirstItem(navigationItemKeyTestChildren($parent))->key)->toBe('existing-child');
});

it('uses legacy associative collection keys as item keys', function (): void {
    $items = EnsureNavigationItemKeysAction::run([
        'a63f834a-b938-4eb1-8cc1-7fd7bceec35d' => [
            'label' => 'Home',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '/'],
        ],
    ]);

    $item = navigationItemKeyTestFirstItem($items);

    expect($items)->toHaveKey('a63f834a-b938-4eb1-8cc1-7fd7bceec35d')
        ->and($item)->toBeInstanceOf(NavigationItemData::class)
        ->and($item->key)->toBe('a63f834a-b938-4eb1-8cc1-7fd7bceec35d');
});

/**
 * @param  DataCollection<int|string, NavigationItemData>  $items
 */
function navigationItemKeyTestFirstItem(DataCollection $items): NavigationItemData
{
    $item = $items->first();

    throw_unless($item instanceof NavigationItemData, RuntimeException::class, 'Expected a navigation item.');

    return $item;
}

/**
 * @return DataCollection<int|string, NavigationItemData>
 */
function navigationItemKeyTestChildren(NavigationItemData $item): DataCollection
{
    throw_unless($item->children instanceof DataCollection, RuntimeException::class, 'Expected navigation item children.');

    return $item->children;
}
