<?php

declare(strict_types=1);

use Capell\Core\Actions\SiteReplicatedAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

function navigationReplicateItem(mixed $item): NavigationItemData
{
    if ($item instanceof NavigationItemData) {
        return $item;
    }

    if (is_array($item)) {
        return NavigationItemData::from($item);
    }

    throw new RuntimeException('Expected replicated navigation item.');
}

function navigationReplicateFirstItem(mixed $items): NavigationItemData
{
    if ($items instanceof DataCollection) {
        return navigationReplicateItem($items[0] ?? null);
    }

    if ($items instanceof Collection) {
        return navigationReplicateItem($items->first());
    }

    if (is_array($items)) {
        return navigationReplicateItem(reset($items));
    }

    throw new RuntimeException('Expected replicated navigation items.');
}

it('replicates a site with navigations and pages', function (): void {
    $languages = Language::factory()->count(2)->create();

    $site = Site::factory()
        ->language($languages->first() instanceof Language ? $languages->first() : Language::query()->find($languages->first()->id))
        ->state(['name' => 'Original'])
        ->create();

    $page = Page::factory()->site($site)->withTranslations()->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->state([
            'items' => [
                [
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'label' => 'Page Link',
                        'pageable_id' => $page->id,
                        'pageable_type' => $page->getMorphClass(),
                    ],
                ],
            ],
        ])
        ->create();

    $clone = SiteReplicatedAction::run($site, [
        'copy_pages' => true,
        'copy_navigations' => true,
    ]);

    capell_expect($clone)->toBeInstanceOf(Site::class)
        ->and($clone->id)->not()->toBe($site->id)
        ->and($clone->name)->toContain('Original');

    $clonedNavigations = $clone->navigations;
    $clonedPages = $clone->pages;

    capell_expect($clonedNavigations)->toHaveCount(1);
    capell_expect($clonedNavigations->first())->not()->toBeNull();
    capell_expect($clonedNavigations->first()?->site_id)->toBe($clone->id);

    capell_expect($clonedPages)->toHaveCount(1)
        ->and($clonedPages->first())->not()->toBeNull();

    if ($clonedPages->first() !== null) {
        capell_expect($clonedPages->first()->site_id)->toBe($clone->id);
    }

    $clonedNavigation = $clonedNavigations->first();
    throw_unless($clonedNavigation instanceof Navigation);

    $originalItem = navigationReplicateFirstItem($navigation->items);
    $clonedItem = navigationReplicateFirstItem($clonedNavigation->items);

    capell_expect($clonedItem->type)->toBe($originalItem->type)
        ->and($clonedItem->data['label'])->toBe('Page Link')
        ->and($clonedItem->data['pageable_id'])->toBe($clonedPages->first()->id ?? null);
});

it('replicates only navigations when setup pages is used', function (): void {
    $language = Language::factory()->create();

    $site = Site::factory()->language($language)->state(['name' => 'Original'])->create();

    Navigation::factory()->site($site)->create();

    $defaultPages = CapellCore::getDefaultPages()->keys()->all();

    $clone = SiteReplicatedAction::run(
        $site,
        [
            'setup_pages' => true,
            'auto_create_pages' => $defaultPages,
            'copy_navigations' => true,
        ],
    );

    capell_expect($clone)->toBeInstanceOf(Site::class);
    capell_expect($clone->id)->not()->toBe($site->id);

    capell_expect($clone->navigations)->toHaveCount(1);
    capell_expect($clone->navigations->first()?->site_id)->toBe($clone->id);

    capell_expect($clone->pages)->not()->toBeEmpty();
});

it('does not copy navigations when disabled', function (): void {
    $site = Site::factory()->create();
    Navigation::factory()->site($site)->count(2)->create();

    $clone = SiteReplicatedAction::run($site, [
        'copy_pages' => true,
        'copy_navigations' => false,
    ]);

    capell_expect($clone->navigations)->toBeEmpty();
});
