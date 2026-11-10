<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

/*
 * Public navigation landmarks are part of the rendered site's accessibility contract. Admin
 * wording for the same concepts lives under separate keys, so renaming admin labels must never
 * change these accessible names.
 */

/**
 * @return array{site: Site, language: Language, page: Page, domain: mixed}
 */
function publicLandmarkContext(string $slug = '/'): array
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $page = $slug === '/'
        ? Page::factory()->site($site)->home()->withTranslations(slug: '/')->create()
        : Page::factory()->site($site)->withTranslations(slug: $slug)->create();

    return ['site' => $site, 'language' => $language, 'page' => $page, 'domain' => $site->siteDomains->first()];
}

/**
 * @param  array{site: Site, language: Language, page: Page, domain: mixed}  $context
 * @param  list<array<string, mixed>>  $items
 */
function publicLandmarkNavigation(array $context, string $key, array $items): void
{
    Navigation::factory()->create([
        'key' => $key,
        'site_id' => $context['site']->getKey(),
        'language_id' => $context['language']->getKey(),
        'items' => $items,
    ]);
}

it('keeps the public accessible name of each menu landmark', function (string $key, string $accessibleName): void {
    $context = publicLandmarkContext();
    publicLandmarkNavigation($context, $key, [
        ['label' => 'Docs', 'type' => NavigationItemType::Link->value, 'data' => ['url' => '/docs']],
    ]);

    $this->blade(
        '<x-capell-navigation::menu :key="$key" :site="$site" :language="$language" :page="$page" :domain="$domain" />',
        ['key' => $key, ...$context],
    )->assertElementExists('nav[aria-label="' . $accessibleName . '"]');
})->with([
    'main' => ['main', 'Main navigation'],
    'footer' => ['footer', 'Footer navigation'],
    'sub-footer' => ['sub-footer', 'Footer utility navigation'],
    'custom key' => ['primary', 'Navigation'],
]);

it('keeps the public accessible name of the breadcrumb landmark', function (): void {
    $context = publicLandmarkContext('/docs');
    publicLandmarkNavigation($context, 'main', [
        [
            'label' => 'Docs',
            'type' => NavigationItemType::Page->value,
            'data' => [
                'site_id' => $context['site']->getKey(),
                'pageable_type' => $context['page']->getMorphClass(),
                'pageable_id' => $context['page']->getKey(),
            ],
        ],
    ]);

    $this->blade(
        '<x-capell-navigation::breadcrumbs key="main" :site="$site" :language="$language" :page="$page" :domain="$domain" />',
        $context,
    )->assertElementExists('nav[aria-label="Breadcrumb navigation"]');
});
