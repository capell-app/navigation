<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Actions\CheckSavedNavigationItemsAction;
use Capell\Navigation\Data\NavigationItemAvailabilityData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationPurpose;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Dom\HTMLDocument;
use Filament\Actions\Testing\TestAction;
use Illuminate\Auth\Access\AuthorizationException;
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

it('keeps the edited menu site when creating another menu', function (): void {
    Site::factory()->default()->withTranslations()->create();
    $site = Site::factory()->withTranslations()->create();
    $navigation = Navigation::factory()->site($site)->create();

    livewire(EditNavigation::class, ['record' => $navigation->getRouteKey()])
        ->mountAction('create')
        ->assertActionDataSet(['site_id' => $site->getKey()]);
});

it('checks saved targets on the real form and agrees with anonymous rendered navigation', function (): void {
    $language = Language::query()->firstOrFail();
    $site = Site::factory()->language($language)->withTranslations(siteDomainData: [
        'scheme' => 'https', 'domain' => 'menu.example.test', 'path' => null,
    ])->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations($language, slug: '/')->create();
    $ready = Page::factory()->site($site)->withTranslations($language, slug: '/ready')->create();
    $scheduled = Page::factory()->site($site)->withTranslations($language, slug: '/scheduled')->create([
        'visible_from' => now()->addDay(),
    ]);
    $disabled = Page::factory()->site($site)->withTranslations($language, slug: '/disabled')->create();
    $disabled->pageUrls()->update(['status' => false]);
    $otherSite = Site::factory()->language($language)->withTranslations()->create();
    $forbidden = Page::factory()->site($otherSite)->withTranslations($language)->create(['name' => 'Confidential target title']);
    $items = [];
    foreach ([$ready, $scheduled, $disabled, $forbidden] as $page) {
        $items[] = [
            'type' => NavigationItemType::Page->value,
            'label' => null,
            'data' => ['pageable_id' => $page->getKey(), 'pageable_type' => $page->getMorphClass()],
        ];
    }
    $items[] = ['label' => 'Hidden item', 'type' => 'link', 'is_visible' => false, 'data' => ['url' => '/hidden']];
    $navigation = Navigation::factory()->site($site)->language($language)->create(['key' => 'availability-menu', 'items' => $items]);
    $action = TestAction::make('check_saved_items')->schemaComponent('savedItemActions');
    $component = livewire(EditNavigation::class, ['record' => $navigation->getRouteKey()])
        ->mountAction($action);
    $dom = HTMLDocument::createFromString($component->getMountedActionModalHtml(), LIBXML_NOERROR, 'UTF-8');
    expect($dom->querySelectorAll('[data-availability="ready"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-availability="not_published"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-availability="target_disabled"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-availability="unavailable"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-availability="hidden"]')->length)->toBe(1);
    $component->assertMountedActionModalSee(__('capell-navigation::generic.saved_items_scope'));
    $check = view('capell-navigation::filament.forms.saved-items-check', [
        'rows' => CheckSavedNavigationItemsAction::run($navigation),
    ])->render();
    expect($check)->not->toContain('Confidential target title');

    auth()->logout();
    BuildNavigationRenderModelAction::flushPageCache();
    $domain = $site->siteDomains()->firstOrFail();
    $public = $this->blade(
        '<x-capell-navigation::menu key="availability-menu" :site="$site" :language="$language" :page="$currentPage" :domain="$domain" />',
        compact('site', 'language', 'currentPage', 'domain'),
    );
    $publicDom = HTMLDocument::createFromString((string) $public, LIBXML_NOERROR, 'UTF-8');
    expect($publicDom->querySelectorAll('nav a')->length)->toBe(1)
        ->and($publicDom->querySelector('nav a')?->getAttribute('href'))->toEndWith('/ready')
        ->and((string) $public)->not->toContain('data-availability', 'Confidential target title', 'Hidden item');

    $scheduled->update(['visible_from' => now()->subMinute()]);
    $disabled->pageUrls()->update(['status' => true]);
    test()->actingAsAdmin();
    $component->unmountAction()->mountAction($action);
    $refreshed = HTMLDocument::createFromString($component->getMountedActionModalHtml(), LIBXML_NOERROR, 'UTF-8');
    expect($refreshed->querySelectorAll('[data-availability="ready"]')->length)->toBe(3);
});

it('checks saved items without reading unsaved item changes', function (): void {
    $navigation = Navigation::factory()->create(['items' => [
        ['type' => 'link', 'label' => 'Saved link', 'data' => ['url' => '/saved']],
    ]]);
    $navigation->items = [];
    $rows = CheckSavedNavigationItemsAction::run($navigation);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]->status)->toBe('ready');
});

it('denies saved item diagnostics to anonymous visitors', function (): void {
    $navigation = Navigation::factory()->create();
    auth()->logout();
    expect(fn () => CheckSavedNavigationItemsAction::run($navigation))
        ->toThrow(AuthorizationException::class);
});

it('explains nested omissions and anonymous audience conditions', function (): void {
    $navigation = Navigation::factory()->create(['items' => [
        ['type' => 'heading', 'label' => 'Hidden parent', 'is_visible' => false, 'children' => [
            ['type' => 'link', 'data' => ['url' => '/child']],
        ]],
        ['type' => 'link', 'data' => ['url' => '/members', 'visibility' => 'authenticated']],
        ['type' => 'link', 'data' => ['url' => '/guests', 'visibility' => 'guests']],
        ['type' => 'link', 'data' => ['url' => 'javascript:alert(1)']],
    ]]);
    $rows = CheckSavedNavigationItemsAction::run($navigation);
    expect(array_map(static fn (NavigationItemAvailabilityData $row): string => $row->status, $rows))
        ->toBe(['hidden', 'parent_omitted', 'audience', 'ready', 'invalid_url'])
        ->and($rows[1]->position)->toBe('1.1');
});
