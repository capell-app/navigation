<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('no two packages ship a migration file with the same basename', function (): void {
    $migrations = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('database/migrations')
        ->name('*.php');

    /** @var array<string, list<string>> $byBasename */
    $byBasename = [];

    foreach ($migrations as $migration) {
        $basename = $migration->getBasename('.php');
        $byBasename[$basename][] = $migration->getRelativePathname();
    }

    $duplicates = array_filter($byBasename, fn (array $paths): bool => count($paths) > 1);

    foreach (array_keys($duplicates) as $name) {
        sort($duplicates[$name]);
    }

    expect($duplicates)->toBe(
        [],
        "New migration filename collision detected across packages — Laravel's batch runner uses the basename as the key:" .
        "\n" . json_encode($duplicates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});

it('no two packages ship a settings migration with the same basename', function (): void {
    $migrations = (new Finder)
        ->in(__DIR__ . '/../../../packages')
        ->path('database/settings')
        ->name('*.php');

    /** @var array<string, list<string>> $byBasename */
    $byBasename = [];

    foreach ($migrations as $migration) {
        $basename = $migration->getBasename('.php');
        $byBasename[$basename][] = $migration->getRelativePathname();
    }

    $duplicates = array_filter($byBasename, fn (array $paths): bool => count($paths) > 1);

    expect($duplicates)->toBe(
        [],
        'Settings migration basenames must be unique — they are tracked by name in the settings.migrations table:' .
        "\n" . json_encode($duplicates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    );
});
