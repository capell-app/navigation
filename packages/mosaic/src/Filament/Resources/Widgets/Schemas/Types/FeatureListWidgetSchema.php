<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class FeatureListWidgetSchema extends DefaultWidgetSchema
{
    #[Override]
    protected function displayTab(Schema $schema): Tab
    {
        return WidgetDisplayTab::make([
            DisplaySection::make([
                ColorSchemeComponent::make('color'),
            ]),
            ComponentSection::make()
                ->statePath('meta'),
        ]);
    }

    #[Override]
    protected function detailsTab(): Tab
    {
        return Tab::make('feature_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-mosaic::form.layout_settings'))
                    ->columns(['default' => 1])
                    ->schema([
                        Select::make('layout')
                            ->label(__('capell-mosaic::form.layout'))
                            ->options(['vertical' => 'Vertical', 'horizontal' => 'Horizontal'])
                            ->default('vertical'),
                    ]),
                Fieldset::make(__('capell-mosaic::form.features'))
                    ->columns(['default' => 1])
                    ->schema([
                        Repeater::make('features')
                            ->label('')
                            ->addActionLabel(__('capell-mosaic::form.add_feature'))
                            ->columns(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('icon')
                                    ->label(__('capell-mosaic::form.icon'))
                                    ->placeholder('✓')
                                    ->maxLength(10),
                                TextInput::make('title')
                                    ->label(__('capell-mosaic::form.title'))
                                    ->placeholder('Feature Name')
                                    ->required(),
                                Textarea::make('description')
                                    ->label(__('capell-mosaic::form.description'))
                                    ->placeholder('Feature explanation text')
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }
}
