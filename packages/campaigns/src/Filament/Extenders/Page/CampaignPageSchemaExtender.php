<?php

declare(strict_types=1);

namespace Capell\Campaigns\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Campaigns\Models\CampaignConversionGoal;
use Capell\Campaigns\Models\CampaignGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

final class CampaignPageSchemaExtender implements PageSchemaExtender
{
    public function extendTranslationComponentsForHook(Schema $schema, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }

    /**
     * @return array<int, Component>
     */
    public function extendSettingsTabComponents(): array
    {
        return [
            Fieldset::make(__('capell-campaigns::generic.campaign'))
                ->statePath('meta.campaign')
                ->columns(['default' => 1, 'lg' => 2])
                ->schema([
                    Select::make('campaign_group_id')
                        ->label(__('capell-campaigns::form.campaign_group'))
                        ->options(fn (): array => CampaignGroup::query()->pluck('name', 'id')->toArray())
                        ->searchable(),
                    Toggle::make('is_landing_page')
                        ->label(__('capell-campaigns::generic.landing_page')),
                    Select::make('primary_goal_id')
                        ->label(__('capell-campaigns::form.primary_goal'))
                        ->options(fn (): array => CampaignConversionGoal::query()->pluck('name', 'id')->toArray())
                        ->searchable(),
                    TextInput::make('utm_content')
                        ->label(__('capell-campaigns::form.utm_content')),
                    TextInput::make('utm_term')
                        ->label(__('capell-campaigns::form.utm_term')),
                ]),
        ];
    }
}
