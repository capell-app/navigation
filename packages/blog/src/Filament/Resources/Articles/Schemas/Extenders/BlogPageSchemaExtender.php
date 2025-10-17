<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Schemas\Extenders;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Layout\Filament\Components\Forms\Page\HeroEditor;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class BlogPageSchemaExtender implements PageSchemaExtender
{
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    public function extendTabs(Schema $schema, array $tabs): array
    {
        return $tabs;
    }

    public function extendTranslationComponents(Schema $schema, array $components): array
    {
        $inserted = false;

        /** @var Component $component */
        foreach ($components as $index => $component) {
            if ($component->getKey(isAbsolute: false) === 'page-title-with-slug-input-wrapper') {
                array_splice($components, $index + 1, 0, [HeroEditor::make()]);
                $inserted = true;
                break;
            }
        }

        if (! $inserted) {
            $components[] = HeroEditor::make();
        }

        return $components;
    }
}
