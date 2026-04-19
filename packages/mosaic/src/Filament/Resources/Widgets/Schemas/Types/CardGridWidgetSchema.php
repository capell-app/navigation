<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Repeater;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Textarea;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;
use Override;

class CardGridWidgetSchema extends DefaultWidgetSchema
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
        return Tab::make('card_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-mosaic::form.grid_settings'))
                    ->columns(['default' => 1])
                    ->schema([
                        Select::make('columns')
                            ->label(__('capell-mosaic::form.columns'))
                            ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4'])
                            ->default(3),
                    ]),
                Fieldset::make(__('capell-mosaic::form.cards'))
                    ->columns(['default' => 1])
                    ->schema([
                        Repeater::make('cards')
                            ->label('')
                            ->addActionLabel(__('capell-mosaic::form.add_card'))
                            ->columns(['default' => 1, 'lg' => 2])
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('capell-mosaic::form.title'))
                                    ->placeholder('Card Title')
                                    ->required(),
                                Textarea::make('description')
                                    ->label(__('capell-mosaic::form.description'))
                                    ->placeholder('Card description text')
                                    ->rows(3),
                                TextInput::make('image')
                                    ->label(__('capell-mosaic::form.image_url'))
                                    ->placeholder('url/to/image.jpg')
                                    ->url(),
                                TextInput::make('link_text')
                                    ->label(__('capell-mosaic::form.link_text'))
                                    ->placeholder('Learn More'),
                                TextInput::make('link_url')
                                    ->label(__('capell-mosaic::form.link_url'))
                                    ->placeholder('/details')
                                    ->url(),
                            ]),
                    ]),
            ]);
    }
}
