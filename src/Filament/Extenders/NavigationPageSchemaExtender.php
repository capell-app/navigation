<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Core\Models\Page;
use Capell\Navigation\Filament\Components\Forms\Page\Tab\NavigationTab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Override;

class NavigationPageSchemaExtender implements PageSchemaExtender
{
    #[Override]
    public function extendTabs(Schema $configurator, array $tabs): array
    {
        $model = $configurator->getModel();

        if ($model === null || ! is_a($model, Page::class, true)) {
            return $tabs;
        }

        $tabs[] = NavigationTab::make();

        return $tabs;
    }

    #[Override]
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    #[Override]
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    #[Override]
    public function extendSidebarComponents(Schema $schema): array
    {
        return [];
    }
}
