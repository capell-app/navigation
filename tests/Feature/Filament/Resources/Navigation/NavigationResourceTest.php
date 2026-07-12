<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Filament\Resources\Navigations\Pages\EditNavigation;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Livewire;

use function Pest\Laravel\get;

uses(CreatesAdminUser::class)
    ->group('navigation');

/** @param SupportCollection<int, int> $assignedSiteIds */
function createScopedUserForNavigationResourceTest(SupportCollection $assignedSiteIds): Authenticatable
{
    $user = new class extends Authenticatable implements FilamentUser
    {
        /** @use HasFactory<Factory<static>> */
        use HasFactory;

        /** @var SupportCollection<int, int> */
        public SupportCollection $assignedSiteIds;

        protected $table = 'users';

        public function canAccessPanel(Panel $panel): bool
        {
            return true;
        }

        /** @return SupportCollection<int, int> */
        public function getAssignedSiteIds(): SupportCollection
        {
            return $this->assignedSiteIds;
        }

        public function isGlobalAdmin(): bool
        {
            return false;
        }
    };

    $user->forceFill([
        'name' => 'Scoped Navigation User',
        'email' => fake()->unique()->safeEmail(),
        'password' => bcrypt('password'),
    ]);
    $user->assignedSiteIds = $assignedSiteIds;

    return $user;
}

test('admin can see navigations', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl())
        ->assertOk();
});

test('navigation appears in web pages navigation after sections', function (): void {
    expect(NavigationResource::getNavigationGroup())->toBe((string) __('capell-admin::navigation.group_websites'))
        ->and(NavigationResource::getNavigationSort())->toBe(6);
});

test('cannot see navigations', function (): void {
    test()->actingAsUser();

    get(NavigationResource::getUrl())
        ->assertForbidden();
});

test('admin can see create navigation', function (): void {
    test()->actingAsAdmin();

    get(NavigationResource::getUrl('create'))->assertOk();
});

test('admin can see edit navigation', function (): void {
    test()->actingAsAdmin();

    $language = Language::factory()->create();
    get(NavigationResource::getUrl('edit', ['record' => Navigation::factory()->language($language)->create()]))->assertOk();
});

test('admin can save navigation with the same key on a different language', function (): void {
    test()->actingAsAdmin();

    $primaryLanguage = Language::factory()->create();
    $secondaryLanguage = Language::factory()->create();
    $site = Site::factory()
        ->language($primaryLanguage)
        ->withTranslations([$primaryLanguage, $secondaryLanguage])
        ->create();

    $navigation = Navigation::factory()
        ->site($site)
        ->language($primaryLanguage)
        ->create([
            'key' => NavigationHandle::Main->value,
        ]);

    Navigation::factory()
        ->site($site)
        ->language($secondaryLanguage)
        ->create([
            'key' => NavigationHandle::Main->value,
        ]);

    Livewire::test(EditNavigation::class, [
        'record' => $navigation->getRouteKey(),
    ])
        ->assertSuccessful()
        ->call('save')
        ->assertHasNoFormErrors();
});

test('navigation queries include globals and assigned sites only for scoped users', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $globalNavigation = Navigation::factory()->create(['site_id' => null]);
    $assignedNavigation = Navigation::factory()->site($assignedSite)->create();
    Navigation::factory()->site($otherSite)->create();

    test()->actingAs(createScopedUserForNavigationResourceTest(collect([navigationResourceModelIntKey($assignedSite)])));

    expect(NavigationResource::getEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalNavigation->getKey(), $assignedNavigation->getKey()])
        ->and(NavigationResource::getGlobalSearchEloquentQuery()->pluck('id')->all())
        ->toEqualCanonicalizing([$globalNavigation->getKey(), $assignedNavigation->getKey()]);
});

function navigationResourceModelIntKey(Model $model): int
{
    $key = $model->getKey();

    if (is_int($key)) {
        return $key;
    }

    return is_string($key) && ctype_digit($key) ? (int) $key : 0;
}
