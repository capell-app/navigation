<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Schemas\Types\Assets;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaTypeEnum;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

abstract class AbstractWidgetAssetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaTypeEnum::WidgetAsset->value;

    abstract protected static function getAssetSchema(Schema $schema): array;

    public static function make(Schema $schema): array
    {
        return [
            Grid::make()
                ->relationship('asset')
                ->columnSpanFull()
                ->schema(static::getAssetSchema($schema)),
        ];
    }
}
