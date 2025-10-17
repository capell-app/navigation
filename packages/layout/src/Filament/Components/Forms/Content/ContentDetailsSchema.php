<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\NameInput;
use Filament\Schemas\Schema;

class ContentDetailsSchema
{
    public static function make(Schema $schema): array
    {
        return [
            NameInput::make('name')
                ->withTitleUpdater(),
            ContentTypeSelect::make('type_id')
                ->live()
                ->withRelation()
                ->withCreateForm()
                ->withEditForm()
                ->unless(
                    in_array($schema->getOperation(), ['create', 'createOption']),
                    fn (ContentTypeSelect $component): ContentTypeSelect => $component->changeConfirmation()
                ),
        ];
    }
}
