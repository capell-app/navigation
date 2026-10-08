<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationChildrenLoadingEnum;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationItemVisibility;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationCacheKeys;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('does not replay authenticated child HTML to a guest using the same locator', function (): void {
    $url = createNavigationAudienceFragmentUrl();
    $this->actingAs(User::factory()->createOne());

    $this->get($url)->assertSuccessful()
        ->assertSee('Public content')
        ->assertSee('Authenticated content')
        ->assertDontSee('Guest content');

    auth()->logout();

    $this->get($url)->assertSuccessful()
        ->assertSee('Public content')
        ->assertDontSee('Authenticated content')
        ->assertSee('Guest content');
});

it('does not serve an unclassified fragment cache entry from an earlier release to guests', function (): void {
    $url = createNavigationAudienceFragmentUrl();
    $queryString = parse_url($url, PHP_URL_QUERY);
    throw_unless(is_string($queryString), RuntimeException::class, 'Expected a locator query string.');
    parse_str($queryString, $query);
    $payload = $query['payload'] ?? null;
    throw_unless(is_string($payload), RuntimeException::class, 'Expected an encrypted locator.');
    $locator = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
    throw_unless(is_array($locator), RuntimeException::class, 'Expected locator fields.');
    $values = [];

    foreach (['navigation', 'site', 'language', 'item', 'path', 'navigation_version', 'page_type', 'page'] as $field) {
        $value = $locator[$field] ?? null;
        throw_unless(is_int($value) || is_string($value), RuntimeException::class, 'Expected a scalar locator field.');
        $values[$field] = (string) $value;
    }

    $legacyKey = NavigationCacheKeys::lazyFragmentKey(implode('|', [
        $values['navigation'],
        'main',
        $values['site'],
        $values['language'],
        $values['item'],
        $values['path'],
        $values['navigation_version'],
        $values['page_type'],
        $values['page'],
    ]));
    $repository = Cache::supportsTags() ? Cache::tags(['navigation']) : Cache::store();
    $repository->put($legacyKey, '<a href="/legacy-private">Legacy authenticated content</a>', now()->addMinutes(5));

    $this->get($url)->assertSuccessful()
        ->assertDontSee('Legacy authenticated content')
        ->assertSee('Public content')
        ->assertSee('Guest content');
});

it('does not reuse a guest child fragment after the requester signs in', function (): void {
    $url = createNavigationAudienceFragmentUrl();

    $firstGuestResponse = $this->get($url)->assertSuccessful()
        ->assertSee('Public content')
        ->assertSee('Guest content')
        ->assertDontSee('Authenticated content');
    $firstGuestContent = $firstGuestResponse->getContent();
    throw_unless(is_string($firstGuestContent), RuntimeException::class, 'Expected a guest fragment response body.');

    $this->actingAs(User::factory()->createOne());

    $this->get($url)->assertSuccessful()
        ->assertSee('Public content')
        ->assertSee('Authenticated content')
        ->assertDontSee('Guest content');

    auth()->logout();

    $this->get($url)->assertSuccessful()->assertContent($firstGuestContent);
});

it('evaluates role and ability visibility for the current authenticated requester', function (): void {
    $url = createNavigationAudienceFragmentUrl();
    $editor = User::factory()->createOne();
    $editor->assignRole(Role::findOrCreate('navigation-fragment-editor', 'web'));
    $editor->givePermissionTo(Permission::findOrCreate('navigation-fragment-special', 'web'));
    $this->actingAs($editor);

    $this->get($url)->assertSuccessful()
        ->assertSee('Editor content')
        ->assertSee('Special content');

    $this->actingAs(User::factory()->createOne());

    $this->get($url)->assertSuccessful()
        ->assertSee('Public content')
        ->assertSee('Authenticated content')
        ->assertDontSee('Editor content')
        ->assertDontSee('Special content');
});

it('rechecks a role after it is revoked and restored for the same requester', function (): void {
    $url = createNavigationAudienceFragmentUrl();
    $role = Role::findOrCreate('navigation-fragment-editor', 'web');
    $actor = User::factory()->createOne();
    $actor->assignRole($role);
    $this->actingAs($actor);

    $this->get($url)->assertSuccessful()->assertSee('Editor content');

    $actor->removeRole($role);
    $this->actingAs($actor->refresh());

    $this->get($url)->assertSuccessful()
        ->assertSee('Authenticated content')
        ->assertDontSee('Editor content');

    $actor->assignRole($role);
    $this->actingAs($actor->refresh());

    $this->get($url)->assertSuccessful()->assertSee('Editor content');
});

it('rechecks an ability after it is revoked and restored for the same requester', function (): void {
    $url = createNavigationAudienceFragmentUrl();
    $permission = Permission::findOrCreate('navigation-fragment-special', 'web');
    $actor = User::factory()->createOne();
    $actor->givePermissionTo($permission);
    $this->actingAs($actor);

    $this->get($url)->assertSuccessful()->assertSee('Special content');

    $actor->revokePermissionTo($permission);
    $this->actingAs($actor->refresh());

    $this->get($url)->assertSuccessful()
        ->assertSee('Authenticated content')
        ->assertDontSee('Special content');

    $actor->givePermissionTo($permission);
    $this->actingAs($actor->refresh());

    $this->get($url)->assertSuccessful()->assertSee('Special content');
});

it('rechecks the parent audience before serving cached child HTML', function (NavigationItemVisibility $visibility): void {
    $actor = User::factory()->createOne();
    $role = Role::findOrCreate('navigation-fragment-editor', 'web');
    $permission = Permission::findOrCreate('navigation-fragment-special', 'web');
    $actor->assignRole($role);
    $actor->givePermissionTo($permission);
    $this->actingAs($actor);
    $url = createNavigationAudienceFragmentUrl($visibility);

    $this->get($url)->assertSuccessful()->assertSee('Public content');

    if ($visibility === NavigationItemVisibility::Authenticated) {
        auth()->logout();
    } elseif ($visibility === NavigationItemVisibility::Role) {
        $actor->removeRole($role);
    } elseif ($visibility === NavigationItemVisibility::Ability) {
        $actor->revokePermissionTo($permission);
    } else {
        throw new LogicException('Expected an authenticated parent audience.');
    }

    if ($visibility !== NavigationItemVisibility::Authenticated) {
        $this->actingAs($actor->refresh());
    }

    $this->get($url)->assertNotFound();
})->with([
    'authenticated parent' => [NavigationItemVisibility::Authenticated],
    'role parent' => [NavigationItemVisibility::Role],
    'ability parent' => [NavigationItemVisibility::Ability],
]);

it('prevents HTTP caches replaying audience-dependent fragments', function (bool $authenticated): void {
    $url = createNavigationAudienceFragmentUrl();

    if ($authenticated) {
        $this->actingAs(User::factory()->createOne());
    }

    $response = $this->get($url)->assertSuccessful()->assertHeader('X-Robots-Tag', 'noindex');

    expect($response->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('no-store'))->toBeTrue()
        ->and($response->headers->hasCacheControlDirective('public'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('max-age'))->toBeFalse()
        ->and($response->headers->hasCacheControlDirective('stale-while-revalidate'))->toBeFalse();
})->with([
    'guest' => [false],
    'authenticated' => [true],
]);

function createNavigationAudienceFragmentUrl(NavigationItemVisibility $parentVisibility = NavigationItemVisibility::Everyone): string
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $page = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $domain = $site->siteDomains()->where('language_id', $language->getKey())->firstOrFail();
    $navigation = Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'visible_from' => now()->subDay(),
        'items' => [[
            'key' => 'parent',
            'label' => 'Parent',
            'type' => NavigationItemType::Link->value,
            'data' => [
                'url' => '/parent',
                'children_loading' => NavigationChildrenLoadingEnum::Lazy->value,
                'visibility' => $parentVisibility->value,
                'role' => 'navigation-fragment-editor',
                'ability' => 'navigation-fragment-special',
            ],
            'children' => [
                ['key' => 'public', 'label' => 'Public content', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/public']],
                ['key' => 'authenticated', 'label' => 'Authenticated content', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/members', 'visibility' => NavigationItemVisibility::Authenticated->value]],
                ['key' => 'guest', 'label' => 'Guest content', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/sign-in', 'visibility' => NavigationItemVisibility::Guests->value]],
                ['key' => 'role', 'label' => 'Editor content', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/editor', 'visibility' => NavigationItemVisibility::Role->value, 'role' => 'navigation-fragment-editor']],
                ['key' => 'ability', 'label' => 'Special content', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/special', 'visibility' => NavigationItemVisibility::Ability->value, 'ability' => 'navigation-fragment-special']],
            ],
        ]],
    ])->refresh();
    $model = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
        navigation: $navigation,
        page: $page,
        site: $site,
        language: $language,
        siteDomain: $domain,
    ));
    $item = $model->items->first();

    throw_unless($item instanceof NavigationItemRenderData, RuntimeException::class, 'Expected a navigation parent render item.');
    $url = $item->lazyFragmentUrl;
    throw_unless(is_string($url) && $url !== '', RuntimeException::class, 'Expected a navigation child fragment URL.');

    return $url;
}
