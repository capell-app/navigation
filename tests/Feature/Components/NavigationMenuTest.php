<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

it('renders a package menu component with explicit frontend context', function (): void {
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
        'items' => [
            [
                'label' => 'Company',
                'type' => NavigationItemType::Heading->value,
            ],
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/docs'],
            ],
            [
                'label' => 'Hidden',
                'type' => NavigationItemType::Link->value,
                'is_visible' => false,
                'data' => ['url' => '/hidden'],
            ],
        ],
    ]);

    $view = $this->blade(
        '<x-capell-navigation::menu key="primary" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    );

    $view
        ->assertElementExists('nav[aria-label="Navigation"]')
        ->assertSee('Company')
        ->assertSee('Docs')
        ->assertElementExists('a[href="/docs"]')
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('a[href=""]'))
        ->assertDontSee('Hidden');
});

it('lets callers override the package menu landmark label', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $currentPage = Page::factory()->site($site)->home()->withTranslations(slug: '/')->create();
    $siteDomain = $site->siteDomains->first();

    Navigation::factory()->create([
        'key' => 'footer',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'label' => 'Terms',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/terms'],
            ],
        ],
    ]);

    $this->blade(
        '<x-capell-navigation::menu key="footer" aria-label="Legal links" :site="$site" :language="$language" :page="$currentPage" :domain="$siteDomain" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage, 'siteDomain' => $siteDomain],
    )
        ->assertElementExists('nav[aria-label="Legal links"]')
        ->assertElementExists(fn (AssertElement $body): BaseAssert => $body->doesntContain('nav[aria-label="Footer navigation"]'));
});

it('resolves a menu domain from preloaded frontend relations without an explicit domain prop', function (): void {
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create()
        ->load('siteDomains');
    $currentPage = Page::factory()
        ->site($site)
        ->home()
        ->withTranslations(slug: '/')
        ->create()
        ->load('pageUrl.siteDomain');

    Navigation::factory()->create([
        'key' => 'primary',
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'items' => [
            [
                'label' => 'Docs',
                'type' => NavigationItemType::Link->value,
                'data' => ['url' => '/docs'],
            ],
        ],
    ]);

    $this->blade(
        '<x-capell-navigation::menu key="primary" :site="$site" :language="$language" :page="$currentPage" />',
        ['site' => $site, 'language' => $language, 'currentPage' => $currentPage],
    )
        ->assertSee('Docs')
        ->assertElementExists('a[href="/docs"]');
});

it('renders nothing when the package menu component has no matching context', function (): void {
    $this->blade('<x-capell-navigation::menu key="missing" />')
        ->assertDontSee('<nav', false);
});
