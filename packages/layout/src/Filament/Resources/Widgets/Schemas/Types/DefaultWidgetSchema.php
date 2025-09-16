<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Components\Forms\FixedWidthSidebar;
use Capell\Admin\Filament\Components\Forms\Media\MediaLibraryFileUpload;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\ActionsRepeater;
use Capell\Layout\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Layout\Filament\Components\Forms\Widget\CreateWidgetDetailsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetComponentFilesSection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetDisplaySection;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetSettingsSchema;
use Capell\Layout\Filament\Components\Forms\Widget\WidgetTranslationsRepeater;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DefaultWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaTypeEnum::Widget->value;

    public static function make(Schema $schema): array
    {
        return match ($schema->getOperation()) {
            'createOption', 'replicate' => static::getCreateOptionSchema($schema),
            'editOption' => static::getEditOptionSchema($schema),
            default => static::getFormSchema($schema),
        };
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            FixedWidthSidebar::make()
                ->mainSchema([
                    WidgetTranslationsRepeater::make($schema)
                        ->section(),
                    ...static::getExtraSchema($schema),
                ])
                ->sidebarSchema(
                    WidgetSettingsSchema::make($schema),
                    contained: true
                ),
        ];
    }

    protected static function getEditOptionSchema(Schema $schema): array
    {
        return [
            WidgetTranslationsRepeater::make($schema),
            ...static::getExtraSchema($schema, withSettingsTab: true),
        ];
    }

    protected static function getCreateOptionSchema(Schema $schema): array
    {
        return [
            CreateWidgetDetailsSchema::make($schema),
            WidgetTranslationsRepeater::make($schema),
            ...static::getExtraSchema($schema),
        ];
    }

    protected static function getExtraSchema(Schema $schema, bool $withSettingsTab = false): array
    {
        return [
            static::getTabs($schema, $withSettingsTab),
        ];
    }

    protected static function getTabs(Schema $schema, bool $withSettingsTab = false): Tabs
    {
        return Tabs::make()
            ->columnSpanFull()
            ->tabs([
                static::getDetailsTab(),
                static::getDisplayTab($schema),
                ...$withSettingsTab ? static::getSettingsTab($schema) : [],
            ]);
    }

    protected static function getDisplayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            Grid::make()
                ->statePath('meta')
                ->schema([
                    WidgetDisplaySection::make([
                        ColorSchemeComponent::make('color_scheme'),
                    ]),
                    WidgetComponentFilesSection::make(),
                ]),
        ]);
    }

    protected static function getDetailsTab(): Tab
    {
        return Tab::make('details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Grid::make()
                    ->schema([
                        MediaLibraryFileUpload::make('image')
                            ->imageDefaults()
                            ->reactive(),
                        Checkbox::make('reverse_order')
                            ->label(__('capell-admin::form.reverse_order'))
                            ->visible(fn (Get $get): bool => (bool) $get('image')),
                    ]),
                Fieldset::make(__('capell-admin::form.actions'))
                    ->schema([
                        ActionsRepeater::make('actions')
                            ->hiddenLabel(),
                    ]),
            ]);
    }

    protected static function getSettingsTab(Schema $schema): Tab
    {
        return Tab::make('settings')
            ->label(__('capell-admin::tab.settings'))
            ->icon('heroicon-o-cog')
            ->statePath('settings')
            ->schema(WidgetSettingsSchema::make($schema));
    }
}
