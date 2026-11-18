<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

it('refuses a second live menu with the same key for all sites and languages', function (): void {
    Navigation::factory()->create(['key' => 'global-footer', 'site_id' => null, 'language_id' => null]);

    expect(fn (): Navigation => Navigation::factory()->create(['key' => 'global-footer', 'site_id' => null, 'language_id' => null]))
        ->toThrow(ValidationException::class)
        ->and(Navigation::query()->where('key', 'global-footer')->count())->toBe(1);
});

it('enforces live menu key uniqueness in the database, not only in the editor', function (): void {
    $indexes = collect(Schema::getIndexes((new Navigation)->getTable()));

    expect($indexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['active_key_scope']))->toBeTrue()
        ->and($indexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['key', 'site_id', 'language_id']))->toBeFalse();
});

it('lets a deleted menu give its key to a replacement and refuses to restore it over that replacement', function (): void {
    $site = Site::factory()->create();
    $deleted = Navigation::factory()->create(['key' => 'main', 'site_id' => $site->id, 'language_id' => null]);
    $deleted->delete();

    $replacement = Navigation::factory()->create(['key' => 'main', 'site_id' => $site->id, 'language_id' => null]);

    expect($replacement->exists)->toBeTrue()
        ->and(fn (): bool => Navigation::withTrashed()->whereKey($deleted->getKey())->firstOrFail()->restore())->toThrow(ValidationException::class)
        ->and(Navigation::onlyTrashed()->whereKey($deleted->getKey())->exists())->toBeTrue();
});

it('keeps the same key free to reuse on another site or language', function (): void {
    $site = Site::factory()->create();
    $otherSite = Site::factory()->create();

    Navigation::factory()->create(['key' => 'main', 'site_id' => $site->id, 'language_id' => null]);
    Navigation::factory()->create(['key' => 'main', 'site_id' => $otherSite->id, 'language_id' => null]);
    Navigation::factory()->create(['key' => 'main', 'site_id' => null, 'language_id' => null]);

    expect(Navigation::query()->where('key', 'main')->count())->toBe(3);
});
