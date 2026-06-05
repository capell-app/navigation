<?php

declare(strict_types=1);

function navigationPackageJson(string $filename): array
{
    $path = dirname(__DIR__, 2) . '/' . $filename;

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
            'model' => 'Capell\\Navigation\\Models\\Navigation',
            'events' => ['saved', 'deleted', 'restored'],
        ],
        [
            'model' => 'Capell\\Core\\Models\\Page',
            'events' => ['updated'],
        ],
        [
            'model' => 'Capell\\Core\\Models\\Site',
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
