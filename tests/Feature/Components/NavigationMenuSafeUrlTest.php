<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\SafeUrl;
use Illuminate\Testing\TestView;

/**
 * @param  list<array<string, mixed>>  $items
 */
function renderNavigationMenuWithItems(array $items): TestView
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'primary',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => $items,
    ]);

    $view = test()->blade(
        '<x-capell-navigation::menu key="primary" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    throw_unless($view instanceof TestView, RuntimeException::class, 'Expected rendered navigation menu test view.');

    return $view;
}

it('neutralises a javascript: url so it never renders as a live href', function (): void {
    $view = renderNavigationMenuWithItems([
        [
            'label' => 'Evil',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => 'javascript:alert(1)'],
        ],
    ]);

    // Label still shown (as a plain span), but no executable href is emitted.
    $view->assertSee('Evil');
    $view->assertDontSee('javascript:alert(1)', false);
    $view->assertDontSee('href="javascript:alert(1)"', false);
});

it('neutralises data: and vbscript: schemes at render time', function (string $dangerousUrl): void {
    $view = renderNavigationMenuWithItems([
        [
            'label' => 'Danger',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => $dangerousUrl],
        ],
    ]);

    $view->assertSee('Danger');
    $view->assertDontSee($dangerousUrl, false);
})->with([
    'data uri' => ['data:text/html,<script>alert(1)</script>'],
    'vbscript' => ['vbscript:msgbox(1)'],
]);

it('still renders legitimate https, relative path and anchor links', function (): void {
    $view = renderNavigationMenuWithItems([
        [
            'label' => 'External',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => 'https://example.com/path'],
        ],
        [
            'label' => 'Internal',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '/docs'],
        ],
        [
            'label' => 'Anchor',
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '#section'],
        ],
    ]);

    $view->assertElementExists('a[href="https://example.com/path"]');
    $view->assertElementExists('a[href="/docs"]');
    $view->assertElementExists('a[href="#section"]');
});

it('classifies navigation url schemes via the shared SafeUrl helper', function (): void {
    expect(SafeUrl::isSafe('https://example.com'))->toBeTrue();
    expect(SafeUrl::isSafe('/relative/path'))->toBeTrue();
    expect(SafeUrl::isSafe('#anchor'))->toBeTrue();
    expect(SafeUrl::isSafe('mailto:hi@example.com'))->toBeTrue();
    expect(SafeUrl::isSafe('tel:+441234567890'))->toBeTrue();

    expect(SafeUrl::isSafe('javascript:alert(1)'))->toBeFalse();
    expect(SafeUrl::isSafe('JavaScript:alert(1)'))->toBeFalse();
    expect(SafeUrl::isSafe('data:text/html,x'))->toBeFalse();
    expect(SafeUrl::isSafe('vbscript:x'))->toBeFalse();
    expect(SafeUrl::isSafe('file:///etc/passwd'))->toBeFalse();
    expect(SafeUrl::isSafe('//evil.example.com'))->toBeFalse();
    expect(SafeUrl::isSafe("java\nscript:alert(1)"))->toBeFalse();

    expect(SafeUrl::sanitise('javascript:alert(1)'))->toBeNull();
    expect(SafeUrl::sanitise('https://example.com'))->toBe('https://example.com');
    expect(SafeUrl::sanitise(null))->toBeNull();
});
