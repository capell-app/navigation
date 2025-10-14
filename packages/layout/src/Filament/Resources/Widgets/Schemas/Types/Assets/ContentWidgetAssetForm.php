<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Layout\Filament\Resources\Contents\Schemas\ContentForm;
use Filament\Schemas\Schema;
use Override;

class ContentWidgetAssetForm extends AbstractWidgetAssetSchema
{
    #[Override]
    protected function getAssetSchema(Schema $schema): array
    {
        return ContentForm::configure($schema)->getComponents();
    }
}
