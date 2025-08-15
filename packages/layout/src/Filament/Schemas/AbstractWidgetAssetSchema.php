<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas;

use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Layout\Enums\SchemaEnum;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;

abstract class AbstractWidgetAssetSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::WidgetAsset->value;

    protected static function getAssetFormSchema(Schema $schema, array $schemaComponents): Group
    {
        return Group::make()
            ->when(
                ! in_array($schema->getOperation(), ['create', 'createOption'], true),
                fn (Group $component): Group => $component->relationship('asset')
            )
            ->schema($schemaComponents);
    }
}
