<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('caches views successfully and writes compiled files', function (): void {
    Artisan::call('view:clear');

    $viewsDir = resource_path('views');
    File::ensureDirectoryExists($viewsDir);

    $validView = $viewsDir . DIRECTORY_SEPARATOR . 'view-cache-test.blade.php';
    $validHtml = '<div>View Cache Test</div>';
    File::put($validView, $validHtml);

    $compiledPath = storage_path('framework/views');
    File::ensureDirectoryExists($compiledPath);

    $beforeFiles = File::files($compiledPath);
    collect($beforeFiles)->each(fn ($file): bool => File::delete($file));

    $result = Artisan::call('view:cache');

    $afterFiles = File::files($compiledPath);

    expect($result)->toBe(0)
        ->and(Artisan::output())
        ->toContain('Blade templates cached successfully')
        ->and($afterFiles)->not->toBeEmpty()
        ->and(count($afterFiles))->toBeGreaterThan(count($beforeFiles));

    foreach ($afterFiles as $file) {
        $path = $file->getPathname();
        expect($path)->toStartWith($compiledPath)
            ->toEndWith('.php')
            ->and(basename($path))->toMatch('/^[A-Za-z0-9_\-]+\.php$/');
    }

    $containsValidHtml = collect($afterFiles)
        ->map(fn (SplFileInfo $file): string => File::get($file->getPathname()))
        ->contains(fn ($content): bool => str_contains($content, $validHtml));

    expect($containsValidHtml)->toBeTrue();

    File::delete($validView);
    Artisan::call('view:clear');
});

it('throws for missing component when caching views', function (): void {
    Artisan::call('view:clear');

    $viewsDir = resource_path('views');
    File::ensureDirectoryExists($viewsDir);

    $tempView = $viewsDir . DIRECTORY_SEPARATOR . 'temp-missing-component.blade.php';
    $bladeContent = <<<'BLADE'
<x-missing-invalid-component />
BLADE;

    File::put($tempView, $bladeContent);

    try {
        Artisan::call('view:cache');
    } catch (InvalidArgumentException $invalidArgumentException) {
        expect($invalidArgumentException->getMessage())
            ->toContain('Unable to locate a class or view for component [missing-invalid-component]');
    } finally {
        File::delete($tempView);
        Artisan::call('view:clear');
    }
});
