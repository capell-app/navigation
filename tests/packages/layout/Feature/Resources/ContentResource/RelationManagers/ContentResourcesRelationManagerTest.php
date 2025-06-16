<?php

declare(strict_types=1);

use Capell\Core\Models;
use Capell\Layout\Filament\Resources\ContentResource;
use Filament\Tables\Actions\CreateAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list content assets', function (): void {
    $content = Models\Content::factory()
        ->has(Models\ContentAsset::factory()->count(5), 'assets')
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($content->assets)
        ->assertTableColumnStateSet('asset.name', value: [$resource->asset->name], record: $resource);
});

it('can search content assets', function (): void {
    $content = Models\Content::factory()
        ->has(Models\ContentAsset::factory()->page(['name' => 'First']), 'assets')
        ->has(Models\ContentAsset::factory()->content(['name' => 'Second']), 'assets')
        ->has(Models\ContentAsset::factory()->media(['name' => 'Third']), 'assets')
        ->has(Models\ContentAsset::factory()->page(['name' => 'Fourth']), 'assets')
        ->has(Models\ContentAsset::factory()->content(['name' => 'Fifth']), 'assets')
        ->create();

    $resource = $content->assets->first()->load('asset');

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->searchTable($resource->asset->name)
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$resource]);
});

test('can create a asset for a widget', function (string $assetType): void {
    $content = Models\Content::factory()->create();

    livewire(ContentResource\RelationManagers\ContentAssetsRelationManager::class, [
        'ownerRecord' => $content,
        'pageClass' => ContentResource\Pages\EditContent::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->mountTableAction(CreateAction::class)
        ->fillForm(
            match ($assetType) {
                'content' => [
                    'asset_type' => app(Models\Content::class)->getMorphClass(),
                    'assets' => [
                        (string) Models\Content::factory()->create()->uuid,
                    ],
                ],
                'media' => [
                    'asset_type' => app(Models\Media::class)->getMorphClass(),
                    'assets' => [
                        (string) Models\Media::factory()->create()->uuid,
                    ],
                ],
                'page' => [
                    'asset_type' => app(Models\Page::class)->getMorphClass(),
                    'assets' => [
                        (string) Models\Page::factory()->create()->uuid,
                    ],
                ],
            },
            formName: 'mountedTableActionForm'
        )
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas(Models\ContentAsset::class, [
        'content_id' => $content->id,
        'asset_type' => $assetType,
    ]);
})->with(['page', 'media', 'content']);
