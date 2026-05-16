<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildPageNavigationReferencesAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

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
