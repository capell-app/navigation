<?php

declare(strict_types=1);

use Capell\Address\Support\FlagIconRenderer;
use Capell\Address\Tests\AddressTestCase;
use Illuminate\Support\Facades\File;

require_once __DIR__ . '/../AddressTestCase.php';

uses(AddressTestCase::class);

beforeEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
});

afterEach(function (): void {
    File::deleteDirectory(public_path('vendor/blade-country-flags'));
});

it('resolves a published blade country flag asset from an iso code', function (): void {
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-fr.svg'), '<svg></svg>');

    expect(resolve(FlagIconRenderer::class)->assetPath('fr'))->toBe('vendor/blade-country-flags/4x3-fr.svg');
});

it('resolves a published blade country flag asset from a blade icon name', function (): void {
    File::ensureDirectoryExists(public_path('vendor/blade-country-flags'));
    File::put(public_path('vendor/blade-country-flags/4x3-gb-eng.svg'), '<svg></svg>');

    expect(resolve(FlagIconRenderer::class)->assetPath('flag-4x3-gb-eng'))->toBe('vendor/blade-country-flags/4x3-gb-eng.svg');
});

it('falls back to a readable label for missing or invalid assets', function (): void {
    $renderer = resolve(FlagIconRenderer::class);

    expect($renderer->assetPath('flag-4x3-fr'))->toBeNull()
        ->and($renderer->fallbackLabel('flag-4x3-fr', 'France'))->toBe('France')
        ->and($renderer->fallbackLabel('../fr'))->toBe('FR');
});
