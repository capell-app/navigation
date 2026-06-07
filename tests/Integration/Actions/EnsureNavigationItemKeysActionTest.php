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

    $parent = $items->first();

    expect($items)->toBeInstanceOf(DataCollection::class)
        ->and($parent)->toBeInstanceOf(NavigationItemData::class)
        ->and($parent->key)->toBeString()
        ->and($parent->key)->not->toBe('')
        ->and($parent->children->first()->key)->toBe('existing-child');
});

it('uses legacy associative collection keys as item keys', function (): void {
    $items = EnsureNavigationItemKeysAction::run([
        'a63f834a-b938-4eb1-8cc1-7fd7bceec35d' => [
            'label' => 'Home',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '/'],
        ],
    ]);

    $item = $items->first();

    expect($items)->toHaveKey('a63f834a-b938-4eb1-8cc1-7fd7bceec35d')
        ->and($item)->toBeInstanceOf(NavigationItemData::class)
        ->and($item->key)->toBe('a63f834a-b938-4eb1-8cc1-7fd7bceec35d');
});
