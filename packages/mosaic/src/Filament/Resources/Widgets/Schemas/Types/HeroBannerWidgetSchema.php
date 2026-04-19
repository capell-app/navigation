<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Widgets\Schemas\Types;

use Capell\Admin\Filament\Components\Forms\MediaLibraryFileUpload;
use Capell\Mosaic\Filament\Components\Forms\ColorSchemeComponent;
use Capell\Mosaic\Filament\Components\Forms\Widget\ComponentSection;
use Capell\Mosaic\Filament\Components\Forms\Widget\DisplaySection;
use Capell\Mosaic\Filament\Components\Forms\Widget\Tab\WidgetDisplayTab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Override;

class HeroBannerWidgetSchema extends DefaultWidgetSchema
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
        return Tab::make('hero_details')
            ->label(__('capell-admin::tab.details'))
            ->icon('heroicon-o-information-circle')
            ->statePath('meta')
            ->schema([
                Fieldset::make(__('capell-mosaic::form.hero_settings'))
                    ->columns(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('title')
                            ->label(__('capell-mosaic::form.title'))
                            ->placeholder('Your Headline Here')
                            ->required(),
                        TextInput::make('subtitle')
                            ->label(__('capell-mosaic::form.subtitle'))
                            ->placeholder('Supporting text explaining the value proposition'),
                        TextInput::make('cta_text')
                            ->label(__('capell-mosaic::form.cta_text'))
                            ->placeholder('Get Started'),
                        TextInput::make('cta_url')
                            ->label(__('capell-mosaic::form.cta_url'))
                            ->placeholder('/signup')
                            ->url(),
                        MediaLibraryFileUpload::make('background_image')
                            ->label(__('capell-mosaic::form.background_image')),
                    ]),
            ]);
    }
}
