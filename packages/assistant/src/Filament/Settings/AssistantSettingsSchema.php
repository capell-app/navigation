<?php

declare(strict_types=1);

namespace Capell\Assistant\Filament\Settings;

use Capell\Admin\Filament\Contracts\HasSchema;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class AssistantSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Checkbox::make('page_content_generator')
                ->label(__('capell-assistant::form.page_content_generator')),
            Checkbox::make('page_title_suggestions')
                ->label(__('capell-assistant::form.page_title_suggestions')),
            Checkbox::make('meta_description_suggestions')
                ->label(__('capell-assistant::form.meta_description_suggestions')),
        ];
    }
}
