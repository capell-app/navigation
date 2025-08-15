<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Filament\Schemas\WidgetAsset\ContentWidgetAssetSchema;
use Capell\Layout\Filament\Schemas\WidgetAsset\MediaWidgetAssetSchema;
use Capell\Layout\Filament\Schemas\WidgetAsset\PageWidgetAssetSchema;
use InvalidArgumentException;

enum WidgetAssetSchemaEnum: string
{
    case Content = ContentWidgetAssetSchema::class;
    case Media = MediaWidgetAssetSchema::class;
    case Page = PageWidgetAssetSchema::class;

    public static function fromName(string $name): self
    {
        return constant(self::class . ('::' . $name))
            ?? throw new InvalidArgumentException('Invalid WidgetAssetSchemaEnum name: ' . $name);
    }
}
