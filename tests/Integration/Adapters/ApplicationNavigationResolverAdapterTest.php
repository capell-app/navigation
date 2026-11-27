<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\ApplicationNavigationResolver;
use Capell\Frontend\Data\ApplicationAreaData;
use Capell\Frontend\Data\ApplicationNavigationItemData;
use Capell\Frontend\Support\Application\ApplicationAreaRegistry;
use Capell\Navigation\Adapters\ApplicationNavigationResolverAdapter;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationItemVisibility;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Registry\NavigationHandleRegistry;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    config()->set('permission.teams', false);
    $this->site = Site::factory()->create();
    $this->language = Language::factory()->create();
    $this->area = new ApplicationAreaData(key: 'customer-account', pageType: 'customer-account', label: 'Customer account');

    Navigation::factory()->create([
        'key' => 'customer-account',
        'site_id' => $this->site->id,
        'language_id' => $this->language->id,
        'name' => 'Internal editor name',
        'meta' => ['editor_note' => 'Draft wording for review'],
        'items' => [
            applicationNavigationLink('Overview', '/account', NavigationItemVisibility::Everyone),
            applicationNavigationLink('Sign in', '/login', NavigationItemVisibility::Guests),
            applicationNavigationLink('Orders', '/account/orders', NavigationItemVisibility::Authenticated, children: [
                applicationNavigationLink('Returns', '/account/orders/returns', NavigationItemVisibility::Authenticated),
            ]),
            applicationNavigationLink('Invoices', '/account/invoices', NavigationItemVisibility::Ability, ['ability' => 'view-invoices']),
            applicationNavigationLink('Team', '/account/team', NavigationItemVisibility::Role, ['role' => 'account-owner']),
            [...applicationNavigationLink('Hidden draft', '/account/draft', NavigationItemVisibility::Everyone), 'is_visible' => false],
        ],
    ]);
});

/**
 * @param  array<string, mixed>  $data
 * @param  list<array<string, mixed>>  $children
 * @return array<string, mixed>
 */
function applicationNavigationLink(string $label, string $url, NavigationItemVisibility $visibility, array $data = [], array $children = []): array
{
    return [
        'label' => $label,
        'type' => NavigationItemType::Link->value,
        'key' => 'item-' . md5($label),
        'is_visible' => true,
        'data' => ['url' => $url, 'visibility' => $visibility->value, ...$data],
        'children' => $children,
    ];
}

/**
 * @param  list<ApplicationNavigationItemData>  $items
 * @return list<string>
 */
function applicationNavigationLabels(array $items): array
{
    return array_map(static fn (ApplicationNavigationItemData $item): string => $item->label, $items);
}

function applicationNavigationUser(bool $isOwner): AuthUser
{
    $user = new class extends AuthUser
    {
        public bool $isOwner = false;

        public function hasRole(string $role): bool
        {
            return $this->isOwner && $role === 'account-owner';
        }
    };
    $user->id = 42;
    $user->isOwner = $isOwner;

    return $user;
}

/**
 * @return list<ApplicationNavigationItemData>
 */
function resolveApplicationNavigation(ApplicationAreaData $area, Site $site, Language $language, string $path = '/account'): array
{
    /** @var ApplicationNavigationResolver $resolver */
    $resolver = app()->make(ApplicationNavigationResolver::class);

    return array_values($resolver->resolve($area, $site, $language, Request::create('https://example.test' . $path)));
}

it('replaces the null Core resolver when the navigation package is installed', function (): void {
    expect(app()->make(ApplicationNavigationResolver::class)::class)->toBe(ApplicationNavigationResolverAdapter::class);
});

it('shows guests only the items meant for everyone and for guests', function (): void {
    expect(applicationNavigationLabels(resolveApplicationNavigation($this->area, $this->site, $this->language)))->toBe(['Overview', 'Sign in']);
});

it('shows a signed-in customer their account items but not guest or permission-gated ones', function (): void {
    $this->actingAs(applicationNavigationUser(isOwner: false));

    $items = resolveApplicationNavigation($this->area, $this->site, $this->language, '/account/orders/returns');

    expect(applicationNavigationLabels($items))->toBe(['Overview', 'Orders'])
        ->and($items[1]->active)->toBeTrue()
        ->and(applicationNavigationLabels($items[1]->children))->toBe(['Returns'])
        ->and($items[1]->children[0]->active)->toBeTrue()
        ->and($items[0]->active)->toBeFalse();
});

it('shows an ability item only when the gate allows it', function (): void {
    $this->actingAs(applicationNavigationUser(isOwner: false));
    Gate::define('view-invoices', static fn (): bool => true);

    expect(applicationNavigationLabels(resolveApplicationNavigation($this->area, $this->site, $this->language)))->toContain('Invoices')->not->toContain('Team');
});

it('shows a role item only to users holding that role', function (): void {
    $this->actingAs(applicationNavigationUser(isOwner: true));

    expect(applicationNavigationLabels(resolveApplicationNavigation($this->area, $this->site, $this->language)))->toContain('Team')->not->toContain('Invoices');
});

it('returns only labels, links and active state with no identifiers or authoring state', function (): void {
    $this->actingAs(applicationNavigationUser(isOwner: true));

    $items = resolveApplicationNavigation($this->area, $this->site, $this->language);
    $payload = json_encode(array_map(static fn (ApplicationNavigationItemData $item): array => $item->toArray(), $items), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    // The menu must really be served before an absence of internals means anything.
    expect(applicationNavigationLabels($items))->toBe(['Overview', 'Orders', 'Team'])
        ->and(array_keys($items[1]->toArray()))->toBe(['label', 'url', 'active', 'children'])
        ->and($items[1]->url)->toBe('/account/orders')
        ->and($payload)->toContain('Returns', '/account/orders/returns');

    expect($payload)
        ->not->toContain('item-')
        ->not->toContain('visibility')
        ->not->toContain('account-owner')
        ->not->toContain('Internal editor name')
        ->not->toContain('Draft wording')
        ->not->toContain('Hidden draft')
        ->not->toContain('"id"');
});

it('returns nothing for an area without a menu', function (): void {
    $area = new ApplicationAreaData(key: 'supplier-portal', pageType: 'supplier-portal');

    // Positive control: the same site and language do serve the area that has a menu.
    expect(applicationNavigationLabels(resolveApplicationNavigation($this->area, $this->site, $this->language)))->toBe(['Overview', 'Sign in']);

    expect(app()->make(ApplicationNavigationResolver::class)->resolve($area, $this->site, $this->language, Request::create('/supplier')))->toBe([]);
});

it('offers declared application areas as navigation handles', function (): void {
    resolve(ApplicationAreaRegistry::class)->register($this->area);
    resolve(ApplicationAreaRegistry::class)->register(new ApplicationAreaData(key: 'supplier-portal', pageType: 'supplier-portal'));

    expect(NavigationHandleRegistry::options())
        ->toHaveKey('main')
        ->toMatchArray(['customer-account' => 'Customer account', 'supplier-portal' => 'supplier-portal'])
        ->and(NavigationHandleRegistry::label('customer-account'))->toBe('Customer account');
});

/**
 * @param  list<array<string, mixed>>  $items
 */
function applicationLinkCheckArea(Site $site, Language $language, array $items): ApplicationAreaData
{
    Navigation::factory()->create(['key' => 'link-checks', 'site_id' => $site->id, 'language_id' => $language->id, 'items' => $items]);

    return new ApplicationAreaData(key: 'link-checks', pageType: 'link-checks');
}

it('never offers a link with an unsafe scheme', function (): void {
    $area = applicationLinkCheckArea($this->site, $this->language, [
        applicationNavigationLink('Orders', '/account/orders', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Script', 'javascript:alert(1)', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Data', 'data:text/html,<script>alert(1)</script>', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Elsewhere', '//evil.example/account', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Help', 'mailto:help@example.test', NavigationItemVisibility::Everyone),
    ]);

    $items = resolveApplicationNavigation($area, $this->site, $this->language);

    expect(applicationNavigationLabels($items))->toBe(['Orders', 'Help'])
        ->and(json_encode(array_map(static fn (ApplicationNavigationItemData $item): array => $item->toArray(), $items), JSON_THROW_ON_ERROR))
        ->not->toContain('javascript')->not->toContain('data:')->not->toContain('evil.example');
});

it('keeps the links beneath a heading that has no link of its own', function (): void {
    $area = applicationLinkCheckArea($this->site, $this->language, [
        applicationNavigationLink('Your account', '', NavigationItemVisibility::Everyone, children: [
            applicationNavigationLink('Profile', '/account/profile', NavigationItemVisibility::Everyone),
            applicationNavigationLink('Staff only', '/account/staff', NavigationItemVisibility::Ability, ['ability' => 'manage-staff']),
        ]),
    ]);

    $items = resolveApplicationNavigation($area, $this->site, $this->language, '/account/profile');

    expect(applicationNavigationLabels($items))->toBe(['Profile'])
        ->and($items[0]->url)->toBe('/account/profile')
        ->and($items[0]->active)->toBeTrue();
});

it('marks an absolute link active only when it points at the current origin', function (): void {
    $area = applicationLinkCheckArea($this->site, $this->language, [
        applicationNavigationLink('Here', 'https://example.test/account', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Other host', 'https://other.example/account', NavigationItemVisibility::Everyone),
        applicationNavigationLink('Other scheme', 'http://example.test/account', NavigationItemVisibility::Everyone),
    ]);

    /** @var ApplicationNavigationResolver $resolver */
    $resolver = app()->make(ApplicationNavigationResolver::class);
    $items = $resolver->resolve($area, $this->site, $this->language, Request::create('https://example.test/account'));

    expect(array_map(static fn (ApplicationNavigationItemData $item): bool => $item->active, $items))->toBe([true, false, false]);
});
