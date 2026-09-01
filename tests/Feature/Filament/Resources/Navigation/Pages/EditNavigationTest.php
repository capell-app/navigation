<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationPurpose;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;

use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

it('can retrieve data', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => $navigation->name,
            'key' => $navigation->key,
        ]);
});

it('shows the source purpose and editable key when replicating a footer navigation', function (): void {
    $navigation = Navigation::factory()->create([
        'key' => 'footer',
        'name' => 'Footer navigation',
    ]);

    $component = livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->mountAction('replicate');

    $component->assertFormSet(['purpose' => NavigationPurpose::Footer], 'mountedActionSchema0');

    expect($component->get('mountedActions.0.data.key'))->toBe('footer');

    $component
        ->assertFormFieldVisible('key', 'mountedActionSchema0')
        ->fillForm([
            'name' => 'Footer navigation copy',
            'key' => 'footer-copy',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect(Navigation::query()->where('key', 'footer-copy')->exists())->toBeTrue();
});

it('shows a registered custom key when replicating a custom navigation', function (): void {
    NavigationHandleRegistry::register('account-menu', 'Account menu');

    try {
        $navigation = Navigation::factory()->create([
            'key' => 'account-menu',
            'name' => 'Account menu',
        ]);

        $component = livewire(EditNavigation::class, [
            'record' => $navigation->getRouteKey(),
        ])
            ->assertSuccessful()
            ->mountAction('replicate');

        $component->assertFormSet(['purpose' => NavigationPurpose::Custom], 'mountedActionSchema0');

        expect($component->get('mountedActions.0.data.key'))->toBe('account-menu');

        $component
            ->assertFormFieldVisible('key', 'mountedActionSchema0')
            ->fillForm([
                'name' => 'Account menu copy',
                'key' => 'account-menu-copy',
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors();

        expect(Navigation::query()->where('key', 'account-menu-copy')->exists())->toBeTrue();
    } finally {
        NavigationHandleRegistry::flush();
    }
});

it('preserves a registered custom key through an edit save', function (): void {
    NavigationHandleRegistry::register('account-menu', 'Account menu');

    try {
        $navigation = Navigation::factory()->create([
            'key' => 'account-menu',
            'name' => 'Account menu',
        ]);

        livewire(EditNavigation::class, [
            'record' => $navigation->getRouteKey(),
        ])
            ->assertSuccessful()
            ->assertSchemaStateSet(['key' => 'account-menu'])
            ->fillForm(['name' => 'Account navigation'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($navigation->refresh())
            ->key->toBe('account-menu')
            ->name->toBe('Account navigation');
    } finally {
        NavigationHandleRegistry::flush();
    }
});

it('shows the saved content impact before a navigation is saved', function (): void {
    $language = Language::query()->firstOrFail();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: [
        'domain' => 'example.test',
        'scheme' => 'http',
        'path' => null,
    ])->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Home']);

    PageUrl::factory()->page($page)->site($site)->language($language)->create(['url' => '/home']);

    $navigation = Navigation::factory()->site($site)->language($language)->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSee(__('capell-navigation::generic.impact_preview'))
        ->assertSee('Home')
        ->assertSee('http://example.test/home')
        ->assertSee(__('capell-navigation::generic.impact_preview_cache'))
        ->assertSee(__('capell-navigation::generic.impact_preview_reversibility'));
});

it('can save', function (): void {
    $navigation = Navigation::factory()->create();
    $newData = Navigation::factory()->make();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => $newData->name,
            'key' => $newData->key,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())
        ->name->toBe($newData->name)
        ->key->toBe($newData->key);
});

test('validates edit navigation', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->fillForm([
            'name' => null,
            'key' => null,
        ])
        ->call('save')
        ->assertHasFormErrors([
            'name' => 'required',
            'key' => 'required',
        ]);
});

it('can delete', function (): void {
    $navigation = Navigation::factory()->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->callAction('delete')
        ->assertHasNoFormErrors();

    assertSoftDeleted($navigation, ['id' => $navigation->id]);
});

it('can edit items with link', function (): void {
    $uuid = (string) Str::uuid();
    $linkType = NavigationItemType::Link;
    $data = ['url' => 'https://example.com'];

    $navigation = Navigation::factory()
        ->items([
            $uuid => [
                'type' => $linkType,
                'label' => 'Initial Label',
                'data' => $data,
            ],
        ])
        ->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('edit')
                ->schemaComponent('items', schema: 'form')
                ->arguments(['cachedRecordKey' => $uuid, 'statePath' => 'data.items.' . $uuid]),
        )
        ->fillForm([
            'label' => 'Test',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())->items->toHaveCount(1)
        ->and($navigation->items[$uuid])
        ->label->toBe('Test')
        ->type->toBe($linkType);
});

it('can edit items with page', function (): void {
    $uuid = (string) Str::uuid();
    $linkType = NavigationItemType::Page;
    $page = Page::factory()->withTranslations()->create();
    $data = ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()];

    $navigation = Navigation::factory()
        ->items([
            $uuid => [
                'type' => $linkType,
                'label' => 'Initial Label',
                'data' => $data,
            ],
        ])
        ->create();

    livewire(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->mountAction(
            TestAction::make('edit')
                ->schemaComponent('items', schema: 'form')
                ->arguments(['cachedRecordKey' => $uuid, 'statePath' => 'data.items.' . $uuid]),
        )
        ->fillForm([
            'label' => 'Test',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    expect($navigation->refresh())->items->toHaveCount(1)
        ->and($navigation->items[$uuid])
        ->label->toBe('Test')
        ->type->toBe($linkType);
});
