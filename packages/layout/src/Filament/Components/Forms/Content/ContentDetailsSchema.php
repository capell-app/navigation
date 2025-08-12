<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\NameInput;

class ContentDetailsSchema
{
    public static function make(): array
    {
        return [
            NameInput::make('name')
                ->withTitleUpdater(),
            ContentTypeSelect::make('type_id')
                ->live()
                ->withRelation()
                ->withCreateForm()
                ->withEditForm(),
        ];
    }
}
