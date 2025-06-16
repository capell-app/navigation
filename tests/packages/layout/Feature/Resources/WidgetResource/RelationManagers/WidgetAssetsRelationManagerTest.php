<?php

declare(strict_types=1);

use Capell\Core\Models;
use Capell\Layout\Filament\Resources\WidgetResource;
use Filament\Tables\Actions\CreateAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list assets for a widget', function (): void {
    $widget = Models\Widget::factory()
        ->has(Models\WidgetAsset::factory()->count(5), 'assets')
        ->create();

    $resource = $widget->assets->first();
    $resource->load('asset');

    livewire(WidgetResource\RelationManagers\WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5)
        ->assertCanSeeTableRecords($widget->assets)
        ->assertTableColumnStateSet('asset.name', [$resource->asset->name], record: $resource);
});

test('can create a asset for a widget', function (string $assetType): void {
    $widget = Models\Widget::factory()->create();

    livewire(WidgetResource\RelationManagers\WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
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

    assertDatabaseHas(Models\WidgetAsset::class, [
        'widget_id' => $widget->id,
        'asset_type' => $assetType,
    ]);
})->with(['page', 'media', 'content']);
