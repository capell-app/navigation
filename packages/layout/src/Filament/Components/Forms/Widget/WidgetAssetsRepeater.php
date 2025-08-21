<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Widget;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Forms\Media\ImageFileUpload;
use Capell\Admin\Filament\Components\Forms\Page\PageSelect;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Models\Page;
use Capell\Layout\Filament\Components\Forms\AssetTypeToggleButtons;
use Capell\Layout\Filament\Components\Forms\Content\ContentSelect;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WidgetAssetsRepeater
{
    public static function make(Schema $schema): Repeater
    {
        return Repeater::make('assets')
            ->label(__('capell-admin::form.assets'))
            ->relationship('widgetAssets')
            ->columnSpanFull()
            ->reorderable()
            ->collapsed()
            ->defaultItems(0)
            ->addActionLabel(__('capell-admin::button.add_asset'))
            ->itemLabel(function (Schema $schema, array $state, string $uuid): string {
                $order = collect($schema->getParentComponent()->getState())
                    ->keys()
                    ->search($uuid) + 1;

                $label = $order . '. ';

                if (empty($state['asset_id'])) {
                    return $label . __('capell-admin::generic.select_resource');
                }

                return $label . self::getAssetName($state) . ' (' . str($state['asset_type'])->title() . ')';
            })
            ->extraItemActions([
                Action::make('editRecord')
                    ->label(function (Repeater $component, array $arguments): string {
                        $itemState = $component->getRawItemState((string) $arguments['item']);

                        return __('capell-admin::button.edit_resource', ['type' => $itemState['asset_type']]);
                    })
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->tooltip(fn (Action $action): string => $action->getLabel())
                    ->hidden(function (Repeater $component, array $arguments): bool {
                        $itemState = $component->getRawItemState((string) $arguments['item']);

                        return empty($itemState['asset_id']);
                    })
                    ->url(
                        function (array $arguments, Repeater $component): ?string {
                            $itemState = $component->getRawItemState((string) $arguments['item']);

                            $assetId = $itemState['asset_id'] ?? null;

                            if (empty($itemState['asset_type']) || empty($assetId)) {
                                return null;
                            }

                            $resource = match ($itemState['asset_type']) {
                                'page' => Page::find($assetId),
                                'content' => Content::find($assetId),
                            };

                            if (! $resource) {
                                throw new Exception(sprintf("Resource '%s' not found for ID '%s'", $itemState['asset_type'], $assetId));
                            }

                            return CapellAdmin::getResource($itemState['asset_type'])::getUrl(
                                'edit',
                                ['record' => $resource]
                            );
                        },
                        shouldOpenInNewTab: true
                    ),
            ])
            ->schema([
                Group::make()
                    ->schema(function (?WidgetAsset $record): array {
                        if ($record instanceof WidgetAsset) {
                            return self::getEditResourceSchema($record);
                        }

                        return self::getCreateResourceSchema();
                    }),
            ]);
    }

    private static function getCreateResourceSchema(): array
    {
        return [
            AssetTypeToggleButtons::make('asset_type')
                ->required()
                ->reactive()
                ->afterStateUpdated(function (Set $set): void {
                    $set('asset_id', null);
                }),
            Group::make()
                ->visible(fn (Get $get): bool => $get('asset_type') === AssetEnum::Page->value)
                ->schema([
                    PageSelect::make('asset_id')
                        ->required()
                        ->withCreateForm()
                        ->withEditForm(),
                ]),
            Group::make()
                ->visible(fn (Get $get): bool => $get('asset_type') === \Capell\Layout\Enums\AssetEnum::Content->value)
                ->schema([
                    ContentSelect::make('asset_id')
                        ->required()
                        ->withCreateForm()
                        ->withEditForm(),
                ]),
        ];
    }

    private static function getEditResourceSchema(?WidgetAsset $record): array
    {
        return [
            Group::make()
                ->schema([
                    match ($record->asset_type) {
                        AssetEnum::Media->value => ImageFileUpload::make('asset_id')
                            ->required(),
                        AssetEnum::Page->value => PageSelect::make('asset_id')
                            ->required()
                            ->withCreateForm()
                            ->withEditForm(),
                        \Capell\Layout\Enums\AssetEnum::Content->value => ContentSelect::make('asset_id')
                            ->required()
                            ->withCreateForm()
                            ->withEditForm(),
                    },
                ]),
        ];
    }

    private static function getAssetName(array $itemState): ?string
    {
        return match ($itemState['asset_type']) {
            'media' => is_array($itemState['asset_id']) ? reset($itemState['asset_id'])['title'] : Media::find($itemState['asset_id']),
            'page' => Page::find($itemState['asset_id'])?->name,
            'content' => Content::find($itemState['asset_id'])?->name
        };
    }
}
