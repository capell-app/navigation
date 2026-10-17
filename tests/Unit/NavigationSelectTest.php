<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Capell\Navigation\Models\Navigation;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

it('offers navigation references in the requested value format', function (bool $useKeys, bool $legacyId): void {
    $navigation = Navigation::factory()->createOne([
        'name' => 'Main Navigation',
        'key' => 'main-navigation',
        'site_id' => null,
        'language_id' => null,
    ]);
    $host = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $select = NavigationSelect::make('navigation');
    if ($useKeys) {
        $select->useKeyAsValue();
    }

    $schema = Schema::make($host)->statePath('data')->components([$select]);
    $schema->getComponents();
    $schema->fill(['navigation' => $useKeys && ! $legacyId ? $navigation->key : $navigation->id]);

    $options = $select->getOptions();
    $expectedValue = $useKeys && ! $legacyId ? $navigation->key : $navigation->id;

    expect(array_keys($options))->toBe([$expectedValue])
        ->and(array_values($options)[0])->toContain('Main Navigation')
        ->and($select->getState())->toBeIn([$expectedValue, (string) $expectedValue]);
})->with([
    'record IDs' => [false, false],
    'portable keys' => [true, false],
    'legacy record ID in a key field' => [true, true],
]);

/** @return array{NavigationSelect, Schema} */
function scopedNavigationSelect(int|string|null $siteId, int|string|null $value): array
{
    $host = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $select = NavigationSelect::make('navigation')->useKeyAsValue();
    $schema = Schema::make($host)->statePath('data')->operation('edit')->components([
        Hidden::make('site_id'),
        Group::make()->statePath('meta')->schema([$select]),
    ]);
    $schema->getComponents();
    $schema->fill(['site_id' => $siteId, 'meta' => ['navigation' => $value]]);

    return [$select, $schema];
}

it('resolves shared keys and legacy IDs only within the offered site candidates', function (string $valueType): void {
    $alpha = Site::factory()->createOne(['name' => 'Alpha']);
    $zulu = Site::factory()->createOne(['name' => 'Zulu']);
    Navigation::factory()->site($alpha)->createOne(['name' => 'Alpha Menu', 'key' => 'shared-menu', 'language_id' => null]);
    $navigation = Navigation::factory()->site($zulu)->createOne(['name' => 'Zulu Menu', 'key' => 'shared-menu', 'language_id' => null]);
    $value = match ($valueType) {
        'integer' => $navigation->id,
        'string' => (string) $navigation->id,
        default => $navigation->key,
    };
    [$select, $schema] = scopedNavigationSelect((string) $zulu->id, $value);
    $hint = $select->getHintActions()[0]->schemaComponent($select);
    $record = $hint->getRecord();

    expect($select->getOptions())->toHaveCount(1)
        ->and($select->getOptionLabel())->toContain('Zulu Menu')->not->toContain('Alpha Menu')
        ->and($record)->toBeInstanceOf(Navigation::class)
        ->and($record instanceof Navigation ? $record->getKey() : null)->toBe($navigation->id)
        ->and($hint->isVisible())->toBeTrue()
        ->and(data_get($schema->getState(), 'meta.navigation'))->toBe($valueType === 'key' ? $navigation->key : $navigation->id);
})->with(['key', 'integer', 'string']);

it('does not resolve legacy IDs from another site', function (bool $stringId): void {
    $alpha = Site::factory()->createOne();
    $zulu = Site::factory()->createOne();
    $navigation = Navigation::factory()->site($alpha)->createOne(['language_id' => null]);
    [$select] = scopedNavigationSelect($zulu->id, $stringId ? (string) $navigation->id : $navigation->id);
    $hint = $select->getHintActions()[0]->schemaComponent($select);

    expect($select->getOptions())->toBe([])
        ->and($select->getOptionLabel(withDefault: false))->toBeNull()
        ->and($hint->getRecord())->toBeNull()
        ->and($hint->isVisible())->toBeFalse();
})->with([false, true]);

it('hides the edit hint when an eligible key is ambiguous', function (string $collision): void {
    $site = Site::factory()->createOne();
    $otherSite = Site::factory()->createOne();
    Navigation::factory()->site($site)->createOne(['key' => 'shared-menu', 'language_id' => null]);
    Navigation::factory()->createOne([
        'key' => 'shared-menu',
        'site_id' => match ($collision) {
            'unscoped sites' => $otherSite->id,
            'global menu' => null,
            default => $site->id,
        },
        'language_id' => $collision === 'languages' ? Language::factory()->createOne()->id : null,
    ]);
    [$select] = scopedNavigationSelect($collision === 'unscoped sites' ? null : $site->id, 'shared-menu');
    $hint = $select->getHintActions()[0]->schemaComponent($select);

    expect($select->getOptions())->toHaveKey('shared-menu')
        ->and($hint->getRecord())->toBeNull()
        ->and($hint->isVisible())->toBeFalse();
})->with(['languages', 'global menu', 'unscoped sites']);

it('resolves an unambiguous global menu within a site', function (): void {
    $site = Site::factory()->createOne();
    $navigation = Navigation::factory()->createOne(['key' => 'global-menu', 'site_id' => null, 'language_id' => null]);
    [$select] = scopedNavigationSelect($site->id, 'global-menu');

    $record = $select->getHintActions()[0]->schemaComponent($select)->getRecord();

    expect($select->getOptions())->toHaveKey('global-menu')
        ->and($record)->toBeInstanceOf(Navigation::class)
        ->and($record instanceof Navigation ? $record->getKey() : null)->toBe($navigation->id);
});

it('keeps numeric keys distinct from legacy IDs in options and the edit hint', function (bool $collision): void {
    $site = Site::factory()->createOne();
    $other = Navigation::factory()->site($site)->createOne(['key' => 'other-menu', 'language_id' => null]);
    $navigation = Navigation::factory()->site($site)->createOne([
        'key' => $collision ? (string) $other->id : '2026',
        'name' => 'Numeric Key Menu',
        'language_id' => null,
    ]);
    [$select, $schema] = scopedNavigationSelect($site->id, $navigation->key);
    $hint = $select->getHintActions()[0]->schemaComponent($select);
    $record = $hint->getRecord();

    expect($record)->toBeInstanceOf(Navigation::class)
        ->and($record instanceof Navigation ? $record->getKey() : null)->toBe($navigation->id)
        ->and($hint->isVisible())->toBeTrue()
        ->and($select->getOptions())->toHaveKey('other-menu')->toHaveKey($navigation->key)
        ->and($select->getOptionLabel())->toContain('Numeric Key Menu')
        ->and(data_get($schema->getState(), 'meta.navigation'))->toBe($navigation->key);
})->with(['numeric key' => false, 'key collides with an ID' => true]);

it('keeps default ID-valued selections when another menu has the same numeric key', function (bool $stringId): void {
    $site = Site::factory()->createOne();
    $navigation = Navigation::factory()->site($site)->createOne(['key' => 'id-menu', 'language_id' => null]);
    Navigation::factory()->site($site)->createOne(['key' => (string) $navigation->id, 'language_id' => null]);
    [$select, $schema] = scopedNavigationSelect($site->id, null);
    $select->useKeyAsValue(false);
    $schema->fill(['site_id' => $site->id, 'meta' => ['navigation' => $stringId ? (string) $navigation->id : $navigation->id]]);
    $record = $select->getHintActions()[0]->schemaComponent($select)->getRecord();

    expect($record instanceof Navigation ? $record->getKey() : null)->toBe($navigation->id)
        ->and(data_get($schema->getState(), 'meta.navigation'))->toBe($navigation->id);
})->with([false, true]);
