<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationPurpose;
use Capell\Navigation\Filament\Resources\Navigations\Pages\CreateNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Testing\TestAction;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    $this->language = Language::factory()->default()->create();
    $this->site = Site::factory()->default()->language($this->language)->withTranslations()->create();
    Blueprint::factory()->navigation()->create();

    test()->actingAsAdmin();
});

it('leads creation with a purpose and derives the main menu key', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->assertFormSet([
            'purpose' => NavigationPurpose::Main,
            'key' => NavigationHandle::Main->value,
        ])
        ->assertSeeText(NavigationPurpose::Main->getLabel())
        ->assertSeeText(NavigationPurpose::Footer->getLabel())
        ->assertSeeText(NavigationPurpose::SubFooter->getLabel())
        ->assertSeeText(NavigationPurpose::Custom->getLabel());
});

it('derives the built-in key and suggested name for every purpose', function (NavigationPurpose $purpose, string $key): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm(['purpose' => $purpose->value])
        ->assertFormSet([
            'key' => $key,
            'name' => $purpose->defaultName(),
        ]);
})->with([
    'main' => [NavigationPurpose::Main, NavigationHandle::Main->value],
    'footer' => [NavigationPurpose::Footer, NavigationHandle::Footer->value],
    'sub-footer' => [NavigationPurpose::SubFooter, NavigationHandle::SubFooter->value],
]);

it('keeps a hand-written name when the purpose changes', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm(['name' => 'Utility bar'])
        ->fillForm(['purpose' => NavigationPurpose::Footer->value])
        ->assertFormSet([
            'name' => 'Utility bar',
            'key' => NavigationHandle::Footer->value,
        ]);
});

it('clears a derived name when the main purpose changes to custom', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm(['purpose' => NavigationPurpose::Custom->value])
        ->assertFormSet([
            'name' => null,
            'key' => null,
        ]);
});

it('hides the key control for built-in purposes and reveals it for custom', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->assertFormFieldHidden('key')
        ->fillForm(['purpose' => NavigationPurpose::Custom->value])
        ->assertFormSet(['key' => null])
        ->assertFormFieldVisible('key');
});

it('defaults a built-in purpose to the current site and language', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->assertFormSet([
            'site_id' => $this->site->getKey(),
            'language_id' => $this->language->getKey(),
        ]);
});

it('creates a main menu without the editor touching the key', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->call('create')
        ->assertHasNoFormErrors();

    $navigation = Navigation::query()->latest()->firstOrFail();

    expect($navigation)
        ->key->toBe(NavigationHandle::Main->value)
        ->name->toBe(NavigationPurpose::Main->defaultName())
        ->site_id->toBe($this->site->getKey())
        ->language_id->toBe($this->language->getKey());
});

it('still requires a key when the purpose is custom', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm(['purpose' => NavigationPurpose::Custom->value])
        ->fillForm(['name' => 'Utility bar'])
        ->call('create')
        ->assertHasFormErrors(['key' => 'required']);
});

it('creates a custom navigation with a registered handle', function (): void {
    NavigationHandleRegistry::register('account-menu', 'Account menu');

    try {
        livewire(CreateNavigation::class)
            ->assertSuccessful()
            ->fillForm([
                'purpose' => NavigationPurpose::Custom->value,
                'name' => 'Account navigation',
                'key' => 'account-menu',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(Navigation::query()->latest()->firstOrFail())
            ->key->toBe('account-menu')
            ->name->toBe('Account navigation');
    } finally {
        NavigationHandleRegistry::flush();
    }
});

it('rejects a navigation site outside the actor assignment', function (): void {
    $otherSite = Site::factory()->language($this->language)->withTranslations()->create();
    $user = new class extends Authenticatable implements FilamentUser
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        /** @var list<string> */
        public array $permissions = [
            'Create:Navigation',
            'ViewAny:Navigation',
            'View:Navigation',
        ];

        /** @var Collection<int, int> */
        public Collection $assignedSiteIds;

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        public function checkPermissionTo(mixed $permission, mixed $guardName = null): bool
        {
            return is_string($permission) && in_array($permission, $this->permissions, true);
        }

        /** @return Collection<int, int> */
        public function getAssignedSiteIds(): Collection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }

        public function hasRole(string $role): bool
        {
            return false;
        }
    };
    $user->assignedSiteIds = collect([(int) $this->site->getKey()]);

    test()->actingAs($user);

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->fillForm([
            'site_id' => $otherSite->getKey(),
            'purpose' => NavigationPurpose::Custom->value,
            'name' => 'Other site navigation',
            'key' => 'other-site-navigation',
        ])
        ->call('create')
        ->assertHasFormErrors(['site_id']);

    expect(Navigation::query()->where('key', 'other-site-navigation')->exists())->toBeFalse();
});

it('reveals the key and reports the collision when the derived key is taken', function (): void {
    Navigation::factory()->create([
        'key' => NavigationHandle::Main->value,
        'site_id' => $this->site->getKey(),
        'language_id' => $this->language->getKey(),
    ]);

    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->assertFormFieldVisible('key')
        ->call('create')
        ->assertHasFormErrors(['key' => 'unique']);
});

it('offers a page start that produces an ordinary editable item', function (): void {
    $component = livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->assertSeeText(__('capell-navigation::generic.starter_empty_heading'))
        ->callAction(TestAction::make('start_with_page')->schemaComponent('starterActions'));

    $items = $component->get('data.items');

    expect($items)->toHaveCount(1)
        ->and(reset($items)['type'])->toBe(NavigationItemType::Page->value);

    $component->assertDontSeeText(__('capell-navigation::generic.starter_empty_heading'));
});

it('offers a custom link start that produces an ordinary editable item', function (): void {
    $component = livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->callAction(TestAction::make('start_with_link')->schemaComponent('starterActions'));

    $items = $component->get('data.items');

    expect($items)->toHaveCount(1)
        ->and(reset($items)['type'])->toBe(NavigationItemType::Link->value);
});

it('builds a starter menu from the published top-level pages of the current site', function (): void {
    $page = Page::factory()
        ->site($this->site)
        ->withTranslations($this->language)
        ->create(['name' => 'About']);

    PageUrl::factory()->page($page)->site($this->site)->create([
        'language_id' => $this->language->getKey(),
        'url' => '/about',
        'status' => true,
    ]);

    $component = livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->callAction(TestAction::make('start_from_published_pages')->schemaComponent('starterActions'));

    $items = $component->get('data.items');

    expect($items)->toHaveCount(1)
        ->and(reset($items)['type'])->toBe(NavigationItemType::Page->value)
        ->and(reset($items)['data']['pageable_id'])->toBe($page->getKey());

    $component->call('create')->assertHasNoFormErrors();

    expect(Navigation::query()->latest()->firstOrFail()->items)->toHaveCount(1);
});

it('explains when no published pages are available to build from', function (): void {
    livewire(CreateNavigation::class)
        ->assertSuccessful()
        ->callAction(TestAction::make('start_from_published_pages')->schemaComponent('starterActions'))
        ->assertNotified(__('capell-navigation::generic.starter_from_pages_none'))
        ->assertSeeText(__('capell-navigation::generic.starter_empty_heading'));
});
