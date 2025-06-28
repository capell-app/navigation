<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\Type;

use Capell\Admin\Filament\Components\Forms\AdminSchemaSelect;
use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Components\Forms\Type\TypeSettingsSchema;
use Capell\Admin\Filament\Schemas\Type\DefaultTypeSchema;
use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Schemas\Widget\DefaultWidgetSchema;
use Filament\Forms;

class WidgetTypeSchema extends DefaultTypeSchema
{
    public static function make(Forms\Form $form): array
    {
        return [
            ...TypeSettingsSchema::make($form),

            Forms\Components\Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    static::getFrontendTab(),
                    static::getAdminTab(),
                ]),
        ];
    }

    protected static function getAdminTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::generic.admin'))
            ->icon('heroicon-m-cog-6-tooth')
            ->columnSpanFull()
            ->columns()
            ->statePath('admin')
            ->schema([
                AdminSchemaSelect::make('default_schema')
                    ->default(fn (): string => DefaultWidgetSchema::getKey())
                    ->setupOptions(SchemaEnum::Widget->value),

                IconPicker::make('icon')
                    ->label(__('capell-admin::form.admin_icon')),

                Forms\Components\Select::make('asset_types')
                    ->label(__('capell-admin::form.asset_type'))
                    ->helperText(__('capell-admin::generic.asset_type_info'))
                    ->multiple()
                    ->options(
                        fn (): array => CapellCore::getAssets()->mapWithKeys(
                            fn (AssetData $asset): array => [$asset->getKey() => $asset->getLabel()]
                        )
                            ->toArray()
                    ),

                Forms\Components\Select::make('content_editor')
                    ->label(__('capell-admin::form.content_editor'))
                    ->helperText(__('capell-admin::generic.content_editor_info'))
                    ->placeholder(__('capell-admin::form.none'))
                    ->default('ContentEditor')
                    ->options(
                        fn (): array => collect(config('capell-admin.editors', []))
                            ->mapWithKeys(fn ($class, $key): array => [$key => $key])
                            ->toArray()
                    ),

                Forms\Components\Checkbox::make('exclude_from_selection')
                    ->label(__('capell-admin::form.exclude_from_selection')),
            ]);
    }

    protected static function getFrontendTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make(__('capell-admin::generic.frontend'))
            ->statePath('meta')
            ->icon('heroicon-m-building-storefront')
            ->columns()
            ->schema([
                WidgetDisplaySection::make(),
                WidgetComponentFilesSection::make(),
            ]);
    }
}
