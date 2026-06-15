<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Contracts\Extenders\SiteSchemaExtender;
use Capell\Core\Contracts\ContentGraph\ContentGraphExtractor;
use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionAdminResource;
use Capell\Core\Contracts\Extensions\RegistersExtensionFrontendComponent;
use Capell\Core\Contracts\Extensions\RegistersExtensionRenderHook;
use Capell\Core\Contracts\Extensions\RegistersExtensionRoute;
use Capell\Core\Contracts\Extensions\RunsExtensionMigration;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Contracts\RenderHookExtensionInterface;
use Capell\Navigation\Console\Commands\DemoCommand;
use Capell\Navigation\Console\Commands\SetupCommand;
use Capell\Navigation\Filament\Configurators\Navigations\DefaultNavigationConfigurator;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Health\NavigationHealthCheck;
use Capell\Navigation\Manifest\NavigationAdminResourceContribution;
use Capell\Navigation\Manifest\NavigationConfiguratorContribution;
use Capell\Navigation\Manifest\NavigationConsoleCommandsContribution;
use Capell\Navigation\Manifest\NavigationContentGraphContribution;
use Capell\Navigation\Manifest\NavigationFrontendComponentsContribution;
use Capell\Navigation\Manifest\NavigationFrontendRouteContribution;
use Capell\Navigation\Manifest\NavigationFrontendRuntimeContribution;
use Capell\Navigation\Manifest\NavigationHealthContribution;
use Capell\Navigation\Manifest\NavigationMigrationsContribution;
use Capell\Navigation\Manifest\NavigationModelsContribution;
use Capell\Navigation\Manifest\NavigationPageTypeContribution;
use Capell\Navigation\Manifest\NavigationRenderHookContribution;
use Capell\Navigation\Manifest\NavigationSchemaExtendersContribution;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\ContentGraph\NavigationContentGraphExtractor;
use Capell\Navigation\Support\NavigationFrontendRuntimeManifestContributor;
use Capell\Navigation\Support\RenderHooks\RegisterFoundationHeaderNavigationHook;
use Capell\Navigation\View\Components\Breadcrumbs;
use Capell\Navigation\View\Components\Header\MainNavigation;
use Capell\Navigation\View\Components\Menu;

function navigationPackagePath(string $filename): string
{
    return dirname(__DIR__, 2) . '/' . $filename;
}

function navigationRepositoryPath(string $filename): string
{
    return dirname(__DIR__, 4) . '/' . $filename;
}

/**
 * @return array<string, mixed>
 */
function navigationPackageJson(string $filename): array
{
    $path = navigationPackagePath($filename);

    expect($path)->toBeFile();

    $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();
    throw_unless(is_array($decoded), RuntimeException::class, 'Expected navigation package JSON to decode to an array.');

    /** @var array<string, mixed> $decoded */

    return $decoded;
}

it('declares direct composer dependencies required by the manifest', function (): void {
    $composer = navigationPackageJson('composer.json');
    $manifest = navigationPackageJson('capell.json');

    expect($composer['require'] ?? [])
        ->toHaveKey('capell-app/core')
        ->toHaveKey('capell-app/admin')
        ->toHaveKey('capell-app/frontend');

    $requires = data_get($manifest, 'dependencies.requires');
    throw_unless(is_array($requires), RuntimeException::class, 'Navigation manifest requires dependencies must be an array.');

    expect($requires)->toContain(
        'capell-app/admin',
        'capell-app/core',
        'capell-app/frontend',
    );
});

it('publishes truthful package capabilities and cache invalidation sources', function (): void {
    $manifest = navigationPackageJson('capell.json');

    expect($manifest['capabilities'] ?? [])->toEqual([
        'navigation-menu-builder',
        'navigation-handle-registry',
        'navigation-mega-menus',
        'navigation-page-field',
        'navigation-render-model',
        'navigation-site-replication',
        'navigation-external-links',
        'navigation-active-state-modes',
        'navigation-breadcrumbs',
        'navigation-conditional-visibility',
    ]);

    expect(data_get($manifest, 'performance.cacheSafety.cacheable'))->toBeTrue()
        ->and(data_get($manifest, 'performance.cacheSafety.variesBy'))->toBe([
            'site',
            'locale',
            'page',
            'domain',
            'guest',
        ]);

    $invalidationSources = data_get($manifest, 'performance.cacheSafety.invalidationSources');
    throw_unless(is_array($invalidationSources), RuntimeException::class, 'Navigation manifest cache invalidation sources must be an array.');

    expect($invalidationSources)->toEqual([
        [
            'model' => Navigation::class,
            'events' => ['saved', 'deleted', 'restored'],
        ],
        [
            'model' => Page::class,
            'events' => ['updated'],
        ],
        [
            'model' => Site::class,
            'events' => ['replicated'],
        ],
    ]);
});

it('declares navigation extension surfaces and contribution contracts', function (): void {
    $manifest = navigationPackageJson('capell.json');

    expect($manifest['database']['requiredTables'] ?? [])->toBe([
        'navigations',
        'navigation_page_references',
    ])
        ->and($manifest['contributionTraceability']['deferredContributions'] ?? null)->toBe([])
        ->and($manifest['contributes'] ?? [])->toContain(
            [
                'type' => 'admin-resource',
                'class' => NavigationAdminResourceContribution::class,
                'resourceClass' => NavigationResource::class,
            ],
            [
                'type' => 'schema-extender',
                'class' => NavigationSchemaExtendersContribution::class,
                'extenderClasses' => [
                    NavigationPageSchemaExtender::class,
                    NavigationSiteExtender::class,
                ],
            ],
            [
                'type' => 'configurator',
                'class' => NavigationConfiguratorContribution::class,
                'configuratorClasses' => [
                    DefaultNavigationConfigurator::class,
                ],
            ],
            [
                'type' => 'page-type',
                'class' => NavigationPageTypeContribution::class,
                'name' => 'navigation',
                'modelClass' => Navigation::class,
            ],
            [
                'type' => 'model',
                'class' => NavigationModelsContribution::class,
                'modelClass' => Navigation::class,
            ],
            [
                'type' => 'migration',
                'class' => NavigationMigrationsContribution::class,
                'tables' => ['navigations', 'navigation_page_references'],
            ],
            [
                'type' => 'route',
                'class' => NavigationFrontendRouteContribution::class,
                'routeNames' => ['capell-navigation.children'],
            ],
            [
                'type' => 'frontend-component',
                'class' => NavigationFrontendComponentsContribution::class,
                'componentClasses' => [
                    Menu::class,
                    Breadcrumbs::class,
                    MainNavigation::class,
                ],
            ],
            [
                'type' => 'configurator',
                'class' => NavigationFrontendRuntimeContribution::class,
                'contributorClass' => NavigationFrontendRuntimeManifestContributor::class,
            ],
            [
                'type' => 'render-hook',
                'class' => NavigationRenderHookContribution::class,
                'hookClass' => RegisterFoundationHeaderNavigationHook::class,
                'location' => 'headerAfter',
                'keys' => [
                    'foundation-header-navigation-default',
                    'foundation-header-navigation-foundation',
                ],
                'cacheSafe' => true,
            ],
            [
                'type' => 'configurator',
                'class' => NavigationContentGraphContribution::class,
                'extractorClass' => NavigationContentGraphExtractor::class,
            ],
            [
                'type' => 'console-command',
                'class' => NavigationConsoleCommandsContribution::class,
                'commands' => [
                    'capell:navigation-setup',
                    'capell:navigation-demo',
                ],
                'commandClasses' => [
                    SetupCommand::class,
                    DemoCommand::class,
                ],
            ],
            [
                'type' => 'health-check',
                'class' => NavigationHealthContribution::class,
                'checkClass' => NavigationHealthCheck::class,
            ],
        );

    expect(class_implements(NavigationAdminResourceContribution::class))->toContain(RegistersExtensionAdminResource::class)
        ->and(class_implements(NavigationSchemaExtendersContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationConfiguratorContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationPageTypeContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationModelsContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationMigrationsContribution::class))->toContain(RunsExtensionMigration::class)
        ->and(class_implements(NavigationFrontendRouteContribution::class))->toContain(RegistersExtensionRoute::class)
        ->and(class_implements(NavigationFrontendComponentsContribution::class))->toContain(RegistersExtensionFrontendComponent::class)
        ->and(class_implements(NavigationFrontendRuntimeContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationRenderHookContribution::class))->toContain(RegistersExtensionRenderHook::class)
        ->and(class_implements(NavigationContentGraphContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationConsoleCommandsContribution::class))->toContain(ExtensionContribution::class)
        ->and(class_implements(NavigationHealthContribution::class))->toContain(ChecksExtensionHealth::class)
        ->and(class_implements(NavigationPageSchemaExtender::class))->toContain(PageSchemaExtender::class)
        ->and(class_implements(NavigationSiteExtender::class))->toContain(SiteSchemaExtender::class)
        ->and(class_implements(NavigationFrontendRuntimeManifestContributor::class))->toContain(FrontendRuntimeManifestContributor::class)
        ->and(class_implements(RegisterFoundationHeaderNavigationHook::class))->toContain(RenderHookExtensionInterface::class)
        ->and(class_implements(NavigationContentGraphExtractor::class))->toContain(ContentGraphExtractor::class);
});

it('uses marketplace and composer copy that describes the package outcome', function (): void {
    $composer = navigationPackageJson('composer.json');
    $manifest = navigationPackageJson('capell.json');

    expect($composer['description'] ?? null)
        ->toBe('Site- and language-scoped navigation menus for Capell: visual menu builder, page & link items, nested dropdowns, active-state rendering, publish scheduling, and multi-site replication.');

    expect(data_get($manifest, 'marketplace.summary'))
        ->toBe('Build and manage multilingual, per-site menus visually — link to any page or URL, nest dropdowns, and render them in your theme with one tag. Active-state, publish windows, and site cloning included.');
});

it('declares shipped marketplace images and screenshot captures', function (): void {
    $manifest = navigationPackageJson('capell.json');
    $marketplaceScreenshots = data_get($manifest, 'marketplace.screenshots');
    throw_unless(is_array($marketplaceScreenshots), RuntimeException::class, 'Navigation marketplace screenshots must be an array.');
    $marketplacePaths = array_column($marketplaceScreenshots, 'path');
    $expectedMarketplacePaths = [
        'docs/assets/marketplace/extension-card.jpg',
        'docs/screenshots/create-edit-navigation-form.png',
        'docs/screenshots/create-edit-navigation-form-dark.png',
        'docs/screenshots/site-relation-manager-for-navigations.png',
        'docs/screenshots/site-relation-manager-for-navigations-dark.png',
    ];

    expect($marketplacePaths)->toEqual($expectedMarketplacePaths);

    foreach ($marketplaceScreenshots as $marketplaceScreenshot) {
        throw_unless(is_array($marketplaceScreenshot), RuntimeException::class, 'Navigation marketplace screenshot entries must be arrays.');
        $path = $marketplaceScreenshot['path'] ?? null;
        throw_unless(is_string($path), RuntimeException::class, 'Navigation marketplace screenshot paths must be strings.');

        expect($marketplaceScreenshot)
            ->toHaveKeys(['path', 'alt', 'caption']);
        expect($marketplaceScreenshot['alt'])->toBeString()->not->toBeEmpty();
        expect($marketplaceScreenshot['caption'])->toBeString()->not->toBeEmpty();
        expect(navigationPackagePath($path))->toBeFile();
    }

    $declaredScreenshotPaths = array_values(array_filter(
        $marketplacePaths,
        static fn (string $marketplacePath): bool => str_starts_with($marketplacePath, 'docs/screenshots/'),
    ));
    sort($declaredScreenshotPaths);

    expect($declaredScreenshotPaths)->toEqual([
        'docs/screenshots/create-edit-navigation-form-dark.png',
        'docs/screenshots/create-edit-navigation-form.png',
        'docs/screenshots/site-relation-manager-for-navigations-dark.png',
        'docs/screenshots/site-relation-manager-for-navigations.png',
    ]);
});

it('keeps the screenshot capture manifest aligned with shipped captures', function (): void {
    $screenshotManifest = navigationPackageJson('docs/screenshots.json');
    $entries = $screenshotManifest['entries'] ?? [];
    throw_unless(is_array($entries), RuntimeException::class, 'Navigation screenshot entries must be an array.');

    expect($entries)->toHaveCount(5);

    $expectedScreenshotPaths = [
        'packages/navigation/docs/screenshots/navigation-admin-index.png',
        'packages/navigation/docs/screenshots/create-edit-navigation-form.png',
        'packages/navigation/docs/screenshots/site-relation-manager-for-navigations.png',
        'packages/navigation/docs/screenshots/page-form-navigation-tab.png',
        'packages/navigation/docs/screenshots/frontend-menu-output.png',
    ];
    $expectedDarkScreenshotPaths = [
        'packages/navigation/docs/screenshots/navigation-admin-index-dark.png',
        'packages/navigation/docs/screenshots/create-edit-navigation-form-dark.png',
        'packages/navigation/docs/screenshots/site-relation-manager-for-navigations-dark.png',
        'packages/navigation/docs/screenshots/page-form-navigation-tab-dark.png',
        'packages/navigation/docs/screenshots/frontend-menu-output-dark.png',
    ];

    expect(array_column($entries, 'screenshotPath'))->toEqual($expectedScreenshotPaths);
    expect(array_column($entries, 'darkScreenshotPath'))->toEqual($expectedDarkScreenshotPaths);

    foreach ($entries as $entry) {
        throw_unless(is_array($entry), RuntimeException::class, 'Navigation screenshot entries must be arrays.');
        $screenshotPath = $entry['screenshotPath'] ?? null;
        $darkScreenshotPath = $entry['darkScreenshotPath'] ?? null;
        throw_unless(is_string($screenshotPath), RuntimeException::class, 'Navigation screenshot paths must be strings.');
        throw_unless(is_string($darkScreenshotPath), RuntimeException::class, 'Navigation dark screenshot paths must be strings.');

        expect($entry)->toHaveKeys(['id', 'screenshotPath', 'darkScreenshotPath']);
        expect(navigationRepositoryPath($screenshotPath))->toBeFile();
        expect(navigationRepositoryPath($darkScreenshotPath))->toBeFile();
    }
});
