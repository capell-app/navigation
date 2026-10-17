<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Resources\Navigations\Pages\ListNavigations;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Factories\Sequence;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

test('can list navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($navigations);
});

it('shows page names for stored navigation items without explicit labels', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->getKey(), 'name' => 'Navigation target page']);
    $navigation = Navigation::factory()->create([
        'site_id' => $site->getKey(),
        'items' => [
            [
                'label' => null,
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->getKey(),
                ],
                'children' => [],
            ],
        ],
    ]);

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$navigation])
        ->assertTableColumnStateSet('items', [$page->name], $navigation);
});

test('uses filter-safe empty state copy when a site filter excludes an existing navigation', function (): void {
    $navigation = Navigation::factory()->create();
    $excludedSite = Site::factory()->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$navigation])
        ->filterTable('filter', [
            'site_id' => (string) $excludedSite->getKey(),
            'language_id' => null,
            'key' => null,
        ])
        ->assertCountTableRecords(0)
        ->assertCanNotSeeTableRecords([$navigation])
        ->assertSee(__('capell-navigation::generic.navigations_empty'))
        ->assertSee(__('capell-navigation::generic.navigations_empty_description'));

    expect(Navigation::query()->count())->toBe(1)
        ->and(__('capell-navigation::generic.navigations_empty'))->toBe('No navigations found')
        ->and(__('capell-navigation::generic.navigations_empty_description'))
        ->toBe('Create a navigation, or adjust the filters to see available records.');
});

test('can search navigations', function (): void {
    $navigations = Navigation::factory()
        ->sequence(fn (Sequence $sequence): array => ['name' => sprintf('Language(%d)', $sequence->index)])
        ->count(3)
        ->create();

    $name = $navigations->random()->name;

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3)
        ->searchTable($name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords($navigations->where('name', $name))
        ->assertCanNotSeeTableRecords($navigations->where('name', '!=', $name));
});

test('can sort navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->sortTable('name')
        ->assertCanSeeTableRecords($navigations->sortBy('name'), inOrder: true);
});

test('can replicate navigation', function (): void {
    $navigation = Navigation::factory()->create();

    $name = $navigation->name . ' (copy)';
    $key = $navigation->key . '-copy';

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $replica = $navigation->replicate();
    $replica->name = $name;
    $replica->key = $key;
    $replica->save();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2);

    assertDatabaseHas('navigations', [
        'name' => $name,
        'key' => $key,
    ]);
});

test('can delete navigation', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(1);

    $navigation->delete();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);

    assertSoftDeleted($navigation, ['id' => $navigation->id]);
});

test('can group delete navigations', function (): void {
    $navigations = Navigation::factory()->count(5)->create();

    livewire(ListNavigations::class)
        ->assertSuccessful()
        ->assertCountTableRecords(5);

    $navigations->each->delete();

    foreach ($navigations as $navigation) {
        assertSoftDeleted($navigation, ['id' => $navigation->id]);
    }
});

it('creates menus in the active site tab', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();

    livewire(ListNavigations::class)
        ->set('activeTab', (string) $site->getKey())
        ->mountAction('create')
        ->assertActionDataSet(['site_id' => $site->getKey()]);
});

it('refreshes site tab counts after menu creation and deletion', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();
    $component = livewire(ListNavigations::class);
    $page = $component->instance();
    throw_unless($page instanceof ListNavigations);

    expect($page->getTabs()[$site->getKey()]->getBadge())->toBe('0');

    $navigation = Navigation::factory()->site($site)->create();
    $component->call('$refresh');

    expect($page->getTabs()[$site->getKey()]->getBadge())->toBe('1');

    $navigation->delete();
    $component->call('$refresh');

    expect($page->getTabs()[$site->getKey()]->getBadge())->toBe('0');
});

it('creates menus in the filtered site when all sites are listed', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();

    livewire(ListNavigations::class)
        ->set('activeTab', 'all')
        ->filterTable('filter', ['site_id' => (string) $site->getKey(), 'language_id' => null, 'key' => null])
        ->mountAction('create')
        ->assertActionDataSet(['site_id' => $site->getKey()]);
});

it('refreshes rendered tab badges after a menu mutation within the request', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();
    $page = livewire(ListNavigations::class)->instance();
    throw_unless($page instanceof ListNavigations);

    expect($page->getCachedTabs()[$site->getKey()]->getBadge())->toBe('0');

    Navigation::factory()->site($site)->create();

    expect($page->getCachedTabs()[$site->getKey()]->getBadge())->toBe('1');
});

it('refreshes badges in a tab schema built before menu creation', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();
    $page = livewire(ListNavigations::class)->instance();
    throw_unless($page instanceof ListNavigations);
    $tabs = $page->getTabsContentComponent()->getDefaultChildComponents();
    throw_unless(is_array($tabs));
    $tab = $tabs[$site->getKey()];
    throw_unless($tab instanceof Tab);

    expect($tab->getBadge())->toBe('0');

    Navigation::factory()->site($site)->create();

    expect($tab->getBadge())->toBe('1');
});
