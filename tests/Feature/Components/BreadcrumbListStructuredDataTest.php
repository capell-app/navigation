<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Testing\TestView;

/**
 * @return array{site: Site, language: Language, page: Page, domain: mixed}
 */
function breadcrumbListContext(): array
{
    BuildNavigationRenderModelAction::flushPageCache();

    $language = Language::factory()->default()->create();
    $site = Site::factory()
        ->language($language)
        ->withTranslations(siteDomainData: ['scheme' => 'https', 'domain' => 'localhost', 'path' => null])
        ->create();
    $page = Page::factory()->site($site)->withTranslations(slug: '/guides/setup')->create();

    return ['site' => $site, 'language' => $language, 'page' => $page, 'domain' => $site->siteDomains->first()];
}

/**
 * @param  array{site: Site, language: Language, page: Page, domain: mixed}  $context
 * @return array<string, mixed>
 */
function breadcrumbListSchema(array $context, string $parentLabel): array
{
    Navigation::factory()->create([
        'key' => 'main',
        'site_id' => $context['site']->getKey(),
        'language_id' => $context['language']->getKey(),
        'items' => [[
            'label' => $parentLabel,
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => '/guides'],
            'children' => [[
                'label' => 'Setup',
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_type' => $context['page']->getMorphClass(),
                    'pageable_id' => $context['page']->getKey(),
                ],
            ]],
        ]],
    ]);

    $view = test()->blade(
        '<x-capell-navigation::breadcrumbs key="main" :site="$site" :language="$language" :page="$page" :domain="$domain" />',
        $context,
    );

    throw_unless($view instanceof TestView, RuntimeException::class, 'Expected a rendered view.');

    $html = (string) $view;

    preg_match_all('#<script\s+type="application/ld\+json"\s*>(.*?)</script>#s', $html, $matches);

    expect($matches[1])->toHaveCount(1);

    $schema = json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);

    throw_unless(is_array($schema), RuntimeException::class, 'Expected a JSON-LD object.');

    /** @var array<string, mixed> $schema */
    return $schema;
}

it('describes the visible breadcrumb trail as a schema.org BreadcrumbList', function (): void {
    $context = breadcrumbListContext();
    $schema = breadcrumbListSchema($context, 'Guides');

    expect($schema)->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Guides', 'item' => url('/guides')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Setup', 'item' => $context['page']->pageUrl?->full_url],
        ],
    ])->and(json_encode($schema))->not->toContain('pageable', 'navigation_id');
});

it('keeps a crumb label from closing the structured data script', function (): void {
    $context = breadcrumbListContext();
    $label = '</script><script>alert(1)</script>';

    $schema = breadcrumbListSchema($context, $label);

    expect(data_get($schema, 'itemListElement.0.name'))->toBe('alert(1)');
});
