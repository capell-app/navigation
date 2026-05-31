<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Filament\Resources\Navigations\Pages\CreateNavigation;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

use Spatie\LaravelData\DataCollection;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();
    Site::factory()->default()->create();
    Blueprint::factory()->navigation()->create();

    test()->actingAsAdmin();
});

test('required fields are required', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->call('create')
        ->assertHasAllFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});

it('can create', function (): void {
    $newData = Navigation::factory()->make();

    $component = livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->first();
    throw_unless($navigation instanceof Navigation);

    $component->assertRedirectToRoute(EditNavigation::getRouteName(), ['record' => $navigation->getRouteKey()]);

    assertDatabaseHas(Navigation::class, [
        'name' => $newData->name,
        'key' => $newData->key,
    ]);
});

it('can add items with link', function (): void {
    $newData = Navigation::factory()->make();
    $type = NavigationItemType::Link;
    $data = ['url' => 'https://example.com'];

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->mountAction(
            TestAction::make('add')
                ->schemaComponent('items', schema: 'form'),
        )
        ->fillForm([
            'label' => 'Test',
            'type' => $type->value,
            'data' => $data,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->firstOrFail();
    $firstItem = firstNavigationItem($navigation);

    expect($navigation)
        ->name->toBe($newData->name)
        ->key->toBe($newData->key)
        ->items->toHaveCount(1)
        ->and($firstItem)
        ->label->toBe('Test')
        ->type->toBe($type);
});

it('can add items with page', function (): void {
    $newData = Navigation::factory()->make();
    $type = NavigationItemType::Page;
    $site = Site::factory()->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations()->create();
    $data = [
        'site_id' => $site->getKey(),
        'pageable_type' => resolve(Page::class)->getMorphClass(),
        'pageable_id' => $page->getKey(),
    ];

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
            'site_id' => $site->getKey(),
        ])
        ->mountAction(
            TestAction::make('add')
                ->schemaComponent('items', schema: 'form'),
        )
        ->fillForm([
            'label' => 'Test',
            'type' => $type->value,
            'data' => $data,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->firstOrFail();
    $firstItem = firstNavigationItem($navigation);

    expect($navigation)
        ->name->toBe($newData->name)
        ->key->toBe($newData->key)
        ->items->toHaveCount(1)
        ->and($firstItem)
        ->label->toBe('Test')
        ->type->toBe($type);
});

function firstNavigationItem(Navigation $navigation): NavigationItemData
{
    $items = $navigation->items;

    throw_unless($items instanceof DataCollection, RuntimeException::class, 'Expected navigation items to be cast to a data collection.');

    $item = $items->first();

    throw_unless($item instanceof NavigationItemData, RuntimeException::class, 'Expected navigation to contain a navigation item.');

    return $item;
}
