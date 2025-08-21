<?php

declare(strict_types=1);

use Capell\Core\Models;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('can list assets for a widget', function (): void {
    $widget = Widget::factory()
        ->has(WidgetAsset::factory()->count(5), 'assets')
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
    $widget = Widget::factory()->create();

    $action = TestAction::make(Filament\Actions\CreateAction::class)->table();

    livewire(WidgetResource\RelationManagers\WidgetAssetsRelationManager::class, [
        'ownerRecord' => $widget,
        'pageClass' => WidgetResource\Pages\EditWidget::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->assertActionExists($action)
        ->mountAction($action)
        ->fillForm(
            match ($assetType) {
                'content' => [
                    'asset_type' => app(Content::class)->getMorphClass(),
                    'asset_id' => [
                        (string) Content::factory()->create()->id,
                    ],
                ],
                'page' => [
                    'asset_type' => app(Models\Page::class)->getMorphClass(),
                    'asset_id' => [
                        (string) Models\Page::factory()->create()->id,
                    ],
                ],
            },
        )
        ->callMountedAction()
        ->assertHasNoFormErrors()
        ->assertCountTableRecords(1);

    assertDatabaseHas(WidgetAsset::class, [
        'widget_id' => $widget->id,
        'asset_type' => $assetType,
    ]);
})->with(['page', 'content']);
