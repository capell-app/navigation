<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\Creator\BlueprintCreator;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Creator\NavigationDemoCreator;
use Illuminate\Support\Collection;

use function Pest\Laravel\assertDatabaseHas;

use Spatie\LaravelData\DataCollection;

/**
 * @param  array<string, mixed>|null  $item
 * @return array<int, array<string, mixed>>
 */
function navigationDemoCreatorChildren(?array $item): array
{
    $children = $item['children'] ?? null;

    throw_unless(is_array($children), RuntimeException::class, 'Expected navigation demo item children.');

    return $children;
}

it('sets up main, footer, and sub-footer navigation', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->default()->create();

    $site = Site::factory()->language($language)->default()->withTranslations($language)->create();

    $typeCreator = resolve(BlueprintCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $homePageType = $typeCreator->homePageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $homePageType->update([
        'status' => true,
    ]);

    $homePage = Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'Home',
            'label' => 'Home',
        ])
        ->create([
            'name' => 'Home',
            'visible_from' => now()->subDay(),
        ]);

    $navigationDemoCreator->setupMainNavigation($site, $language, $homePage);
    $navigationDemoCreator->setupFooterNavigation($site, $language);
    $navigationDemoCreator->setupSubFooterNavigation($site, $language);

    expect($site->navigations()->count())->toBeGreaterThanOrEqual(1);
});

it('merges generated footer items into an existing navigation with persisted items', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->english()->create();

    Site::factory()->default()->create();

    $site = Site::factory()
        ->language($language)
        ->withTranslations($language)
        ->create([
            'default' => false,
            'name' => 'Footer Demo Site',
        ]);

    $typeCreator = resolve(BlueprintCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $defaultPageType = $typeCreator->defaultPageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $defaultPageType->update([
        'default' => true,
        'status' => true,
    ]);

    $parentPage = Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'About',
            'label' => 'About',
        ])
        ->create([
            'name' => 'About',
            'visible_from' => now()->subDay(),
        ]);

    Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->parent($parentPage)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'Team',
            'label' => 'Team',
        ])
        ->create([
            'name' => 'Team',
            'visible_from' => now()->subDay(),
        ]);

    Navigation::factory()
        ->site($site)
        ->language($language)
        ->create([
            'key' => NavigationHandle::Footer->value,
            'blueprint_id' => $navigationType->id,
            'items' => [
                'existing-link' => [
                    'label' => 'Existing Link',
                    'type' => NavigationItemType::Link->value,
                    'data' => [
                        'url' => 'https://example.com/existing',
                    ],
                    'children' => [],
                ],
            ],
        ]);

    $navigationDemoCreator->setupFooterNavigation($site, $language);

    $navigation = Navigation::query()
        ->where('key', NavigationHandle::Footer->value)
        ->where('site_id', $site->id)
        ->where('language_id', $language->id)
        ->firstOrFail();

    expect($navigation)->toBeInstanceOf(Navigation::class)
        ->and($navigation->items)->not()->toBeNull();

    $navigationItems = navigationDemoCreatorItems($navigation);
    $navigationLabels = $navigationItems->pluck('label')->all();

    expect($navigationItems)->toHaveCount(2)
        ->and($navigationLabels)
        ->toContain('Existing Link', 'About');

    $aboutNavigationItem = $navigationItems->firstWhere('label', 'About');

    expect($aboutNavigationItem)->not()->toBeNull()
        ->and($aboutNavigationItem)
        ->toMatchArray([
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $parentPage->getKey(),
                'pageable_type' => $parentPage->getMorphClass(),
            ],
        ]);
});

it('creates main navigation with the home page and eligible top-level pages', function (): void {
    $navigationDemoCreator = resolve(NavigationDemoCreator::class);

    $language = Language::factory()->english()->create();

    $site = Site::factory()
        ->language($language)
        ->default()
        ->withTranslations($language)
        ->create(['name' => 'Demo Site']);

    $typeCreator = resolve(BlueprintCreator::class);
    $navigationType = $typeCreator->createNavigationType();
    $defaultPageType = $typeCreator->defaultPageType();
    $homePageType = $typeCreator->homePageType();

    $navigationType->update([
        'default' => true,
        'status' => true,
    ]);

    $defaultPageType->update([
        'default' => true,
        'status' => true,
    ]);

    $homePageType->update([
        'status' => true,
    ]);

    $createPublishedPage = function (string $name, array $translationData = [], ?Page $parentPage = null) use ($site, $language, $defaultPageType): Page {
        $pageFactory = Page::factory()
            ->site($site)
            ->type($defaultPageType);

        if ($parentPage instanceof Page) {
            $pageFactory = $pageFactory->parent($parentPage);
        }

        return $pageFactory->withTranslations(
            capell_test_collect([$language]),
            [
                'title' => $name,
                'label' => $name,
                ...$translationData,
            ],
        )
            ->create([
                'name' => $name,
                'visible_from' => now()->subDay(),
            ]);
    };

    $home = Page::factory()
        ->site($site)
        ->type($homePageType)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'Home',
            'label' => 'Home',
        ])
        ->create([
            'name' => 'Home',
            'visible_from' => now()->subDay(),
        ]);

    $aboutPage = $createPublishedPage('About');
    $createPublishedPage('Team', [], $aboutPage);

    $servicesPage = $createPublishedPage('Services');
    $createPublishedPage('Consulting', [], $servicesPage);

    $createPublishedPage('Standalone');

    $draftPage = Page::factory()
        ->site($site)
        ->type($defaultPageType)
        ->withTranslations(capell_test_collect([$language]), [
            'title' => 'Draft Section',
            'label' => 'Draft Section',
        ])
        ->create([
            'name' => 'Draft Section',
            'visible_from' => now()->addDay(),
        ]);

    $createPublishedPage('Hidden Draft Child', [], $draftPage);

    $navigationDemoCreator->setupMainNavigation($site, $language, $home);

    assertDatabaseHas('navigations', [
        'key' => NavigationHandle::Main->value,
        'site_id' => $site->id,
        'language_id' => $language->id,
    ]);

    $navigation = Navigation::query()
        ->where('key', NavigationHandle::Main->value)
        ->where('site_id', $site->id)
        ->where('language_id', $language->id)
        ->firstOrFail();

    expect($navigation)->toBeInstanceOf(Navigation::class)
        ->and($navigation->items)->not()->toBeNull();

    $navigationItems = navigationDemoCreatorItems($navigation);
    $navigationLabels = $navigationItems->pluck('label')->all();

    expect($navigationItems)->toHaveCount(4)
        ->and($navigationItems->first())
        ->toMatchArray([
            'label' => 'Home',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $site->id,
                'pageable_id' => $home->getKey(),
                'pageable_type' => $home->getMorphClass(),
                'hidden_label' => true,
                'icon' => 'heroicon-o-home',
            ],
        ])
        ->and($navigationLabels)
        ->toBe(['Home', 'Services', 'About', 'Standalone'])
        ->not()->toContain('Draft Section');

    $aboutNavigationItem = $navigationItems->firstWhere('label', 'About');
    $servicesNavigationItem = $navigationItems->firstWhere('label', 'Services');

    expect($aboutNavigationItem)->not()->toBeNull()
        ->and(capell_test_collect(navigationDemoCreatorChildren($aboutNavigationItem))->values()->pluck('label')->all())
        ->toContain('Team')
        ->and($servicesNavigationItem)->not()->toBeNull()
        ->and(capell_test_collect(navigationDemoCreatorChildren($servicesNavigationItem))->values()->pluck('label')->all())
        ->toContain('Consulting');
});

/**
 * @return Collection<int, array<string, mixed>>
 */
function navigationDemoCreatorItems(Navigation $navigation): Collection
{
    $items = $navigation->items;

    if ($items instanceof DataCollection) {
        return capell_test_collect($items->toArray())->values();
    }

    if (is_array($items)) {
        return capell_test_collect($items)->values();
    }

    throw new RuntimeException('Expected navigation items to be available.');
}
