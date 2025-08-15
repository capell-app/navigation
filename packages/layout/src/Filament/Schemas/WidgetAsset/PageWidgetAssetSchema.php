<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\WidgetAsset;

use Capell\Admin\Filament\Resources\PageResource;
use Capell\Layout\Filament\Schemas\AbstractWidgetAssetSchema;
use Filament\Schemas\Schema;

class PageWidgetAssetSchema extends AbstractWidgetAssetSchema
{
    public static function make(Schema $schema): array
    {
        return [
            self::getAssetFormSchema($schema, PageResource::getFormSchema($schema)),
        ];
    }
}
