<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;

function navigationPackagePath(string $filename): string
{
    return dirname(__DIR__, 2) . '/' . $filename;
}

function navigationRepositoryPath(string $filename): string
{
    return dirname(__DIR__, 4) . '/' . $filename;
}

function navigationPackageJson(string $filename): array
{
    $path = navigationPackagePath($filename);

    expect($path)->toBeFile();

    $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

    expect($decoded)->toBeArray();

    return $decoded;
}

it('declares direct composer dependencies required by the manifest', function (): void {
    $composer = navigationPackageJson('composer.json');
    $manifest = navigationPackageJson('capell.json');

    expect($composer['require'] ?? [])
        ->toHaveKey('capell-app/core')
        ->toHaveKey('capell-app/admin')
        ->toHaveKey('capell-app/frontend');

    expect($manifest['dependencies']['requires'] ?? [])->toContain(
        'capell-app/admin',
        'capell-app/core',
        'capell-app/frontend',
    );
});

it('publishes truthful package capabilities and cache invalidation sources', function (): void {
    $manifest = navigationPackageJson('capell.json');

    expect($manifest['capabilities'] ?? [])->toEqual([
        'navigation-menu-builder',
        'navigation-page-field',
        'navigation-render-model',
        'navigation-site-replication',
    ]);

    expect($manifest['performance']['cacheSafety']['invalidationSources'] ?? [])->toEqual([
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

it('uses marketplace and composer copy that describes the package outcome', function (): void {
    $composer = navigationPackageJson('composer.json');
    $manifest = navigationPackageJson('capell.json');

    expect($composer['description'] ?? null)
        ->toBe('Site- and language-scoped navigation menus for Capell: visual menu builder, page & link items, nested dropdowns, active-state rendering, publish scheduling, and multi-site replication.');

    expect($manifest['marketplace']['summary'] ?? null)
        ->toBe('Build and manage multilingual, per-site menus visually — link to any page or URL, nest dropdowns, and render them in your theme with one tag. Active-state, publish windows, and site cloning included.');
});

it('declares shipped marketplace images and screenshot captures', function (): void {
    $manifest = navigationPackageJson('capell.json');
    $marketplaceScreenshots = $manifest['marketplace']['screenshots'] ?? [];
    $marketplacePaths = array_column($marketplaceScreenshots, 'path');
    $expectedMarketplacePaths = [
        'docs/assets/marketplace/extension-card.jpg',
        'docs/assets/marketplace/hero-desktop.jpg',
        'docs/assets/marketplace/hero-mobile.jpg',
        'docs/screenshots/navigation-admin-index.png',
        'docs/screenshots/navigation-admin-index-dark.png',
        'docs/screenshots/create-edit-navigation-form.png',
        'docs/screenshots/create-edit-navigation-form-dark.png',
        'docs/screenshots/site-relation-manager-for-navigations.png',
        'docs/screenshots/site-relation-manager-for-navigations-dark.png',
        'docs/screenshots/page-form-navigation-tab.png',
        'docs/screenshots/page-form-navigation-tab-dark.png',
        'docs/screenshots/frontend-menu-output.png',
        'docs/screenshots/frontend-menu-output-dark.png',
    ];

    expect($marketplacePaths)->toEqual($expectedMarketplacePaths);

    foreach ($marketplaceScreenshots as $marketplaceScreenshot) {
        expect($marketplaceScreenshot)
            ->toHaveKeys(['path', 'alt', 'caption']);
        expect($marketplaceScreenshot['alt'])->toBeString()->not->toBeEmpty();
        expect($marketplaceScreenshot['caption'])->toBeString()->not->toBeEmpty();
        expect(navigationPackagePath($marketplaceScreenshot['path']))->toBeFile();
    }

    $declaredScreenshotPaths = array_values(array_filter(
        $marketplacePaths,
        static fn (string $marketplacePath): bool => str_starts_with($marketplacePath, 'docs/screenshots/'),
    ));
    sort($declaredScreenshotPaths);

    $shippedScreenshotPaths = array_map(
        static fn (string $shippedScreenshotPath): string => 'docs/screenshots/' . basename($shippedScreenshotPath),
        glob(navigationPackagePath('docs/screenshots/*.png')) ?: [],
    );
    sort($shippedScreenshotPaths);

    expect($declaredScreenshotPaths)->toEqual($shippedScreenshotPaths);
});

it('keeps the screenshot capture manifest aligned with shipped captures', function (): void {
    $screenshotManifest = navigationPackageJson('docs/screenshots.json');
    $entries = $screenshotManifest['entries'] ?? [];

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
        expect($entry)->toHaveKeys(['id', 'screenshotPath', 'darkScreenshotPath']);
        expect(navigationRepositoryPath($entry['screenshotPath']))->toBeFile();
        expect(navigationRepositoryPath($entry['darkScreenshotPath']))->toBeFile();
    }
});
