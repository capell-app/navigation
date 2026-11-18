<?php

declare(strict_types=1);

use BladeUI\Icons\Components\Svg;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\SafeIcon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Testing\TestView;

/**
 * @param  list<array<string, mixed>>  $items
 */
function renderAccessibleNavigationMenu(array $items, string $component = 'menu', bool $withPageItems = false): TestView
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    if ($withPageItems) {
        $otherPage = Page::factory()->site($site)->withTranslations()->create();
        $items = [
            ['label' => 'Current page', 'type' => NavigationItemType::Page->value, 'data' => ['pageable_id' => $currentPage->getKey(), 'pageable_type' => $currentPage->getMorphClass()]],
            ['label' => 'Other page', 'type' => NavigationItemType::Page->value, 'data' => ['pageable_id' => $otherPage->getKey(), 'pageable_type' => $otherPage->getMorphClass()]],
        ];
    }

    Navigation::factory()->create([
        'key' => 'primary',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => $items,
    ]);

    $view = test()->blade(
        '<x-capell-navigation::' . $component . ' key="primary" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    throw_unless($view instanceof TestView, RuntimeException::class, 'Expected rendered navigation test view.');

    return $view;
}

it('marks only the active link with aria-current page', function (): void {
    $html = (string) renderAccessibleNavigationMenu([], withPageItems: true);

    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toMatch('/aria-current="page"[^>]*>\s*Current page/s');
});

it('shows a page item without a url for this language as text rather than a link to the top of the page', function (): void {
    $view = renderAccessibleNavigationMenu([
        ['label' => 'No url yet', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '#']],
    ]);

    $view->assertSee('No url yet');
    $view->assertDontSee('href="#"', false);
});

it('gives aria-current only to the exact current page and not to its ancestors', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $current = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $parent = Page::factory()->site($site)->withTranslations()->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'primary',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [[
            'label' => 'Parent item',
            'type' => NavigationItemType::Page->value,
            'data' => ['pageable_id' => $parent->getKey(), 'pageable_type' => $parent->getMorphClass()],
            'children' => [[
                'label' => 'Child item',
                'type' => NavigationItemType::Page->value,
                'data' => ['pageable_id' => $current->getKey(), 'pageable_type' => $current->getMorphClass()],
            ]],
        ]],
    ]);

    $view = test()->blade(
        '<x-capell-navigation::menu key="primary" :site="$site" :language="$language" :page="$current" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'current' => $current, 'siteDomain' => $siteDomain],
    );

    throw_unless($view instanceof TestView, RuntimeException::class, 'Expected rendered navigation test view.');

    $html = (string) $view;

    expect(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toMatch('/aria-current="page"[^>]*>\s*Child item/s');
});

it('resolves only Blade Icons aliases and rejects other registered components', function (): void {
    Blade::component('fake-set-icon', Svg::class);
    Blade::component('plain-class-alias', stdClass::class);

    expect(SafeIcon::resolve('heroicon-o-home'))->toBe('heroicon-o-home')
        ->and(SafeIcon::resolve('fake-set-icon'))->toBe('fake-set-icon')
        ->and(SafeIcon::resolve('dynamic-component'))->toBeNull()
        ->and(SafeIcon::resolve('plain-class-alias'))->toBeNull()
        ->and(SafeIcon::resolve('unregistered-anonymous-name'))->toBeNull()
        ->and(SafeIcon::resolve('Evil\\Component'))->toBeNull()
        ->and(SafeIcon::resolve('<script>'))->toBeNull()
        ->and(SafeIcon::resolve(''))->toBeNull()
        ->and(SafeIcon::resolve(null))->toBeNull();
});
