<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Events\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Type;
use Capell\Events\Filament\Configurators\Events\EventPageConfigurator;
use Capell\Events\Filament\Resources\Events\EventResource;
use Capell\Events\Models\Event;
use Capell\Events\Support\Creator\EventsCreator;
use Filament\Schemas\Schema;

class EventForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();

        if ($record instanceof Pageable && $record->type_id !== null) {
            $type = Type::query()->find($record->type_id);
            $adminType = $type instanceof Type
                ? $resolver->resolveForType($type, ConfiguratorTypeEnum::Page, EventPageConfigurator::getKey())
                : EventPageConfigurator::class;

            if (method_exists($record, 'type')) {
                $record->loadMissing('type');
            }

            return $adminType::configure($configurator, ConfiguratorContextData::forEdit(ConfiguratorTypeEnum::Page));
        }

        $defaultType = Event::getDefaultType(EventResource::class);

        if (! $defaultType instanceof Type) {
            $defaultType = resolve(EventsCreator::class)->createEventPageType();
        }

        $adminType = $resolver->resolveForType($defaultType, ConfiguratorTypeEnum::Page, EventPageConfigurator::getKey());
        $operation = $configurator->getOperation();

        return $adminType::configure($configurator, new ConfiguratorContextData(
            ConfiguratorTypeEnum::Page,
            in_array($operation, ['create', 'createOption', 'edit', 'editOption', 'replicate'], true) ? $operation : 'create',
            $defaultType->key,
        ));
    }
}
