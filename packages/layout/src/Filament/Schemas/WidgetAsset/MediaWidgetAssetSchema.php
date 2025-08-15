<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Layout\Filament\Components\Forms\MediaSchema;
use Capell\Layout\Filament\Schemas\AbstractWidgetAssetSchema;
use Filament\Schemas\Schema;

class MediaWidgetAssetSchema extends AbstractWidgetAssetSchema
{
    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema, MediaSchema::make()),
        ];
    }
}
