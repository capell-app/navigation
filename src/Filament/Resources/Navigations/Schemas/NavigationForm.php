<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Support\Configurators\ConfiguratorResolver;
use Capell\Core\Models\Blueprint;
use Capell\Navigation\Enums\NavigationConfiguratorTypeEnum;
use Capell\Navigation\Filament\Configurators\Navigations\DefaultNavigationConfigurator;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class NavigationForm implements FormConfigurator
{
    public static function configure(Schema $configurator, ?ConfiguratorContextData $context = null): Schema
    {
        $resolver = resolve(ConfiguratorResolver::class);
        $record = $configurator->getRecord();

        if ($record instanceof Model && $record->exists) {
            $typeId = $record->getAttribute('blueprint_id');

            /** @var class-string<Blueprint> $model */
            $model = Blueprint::class;

            $type = $typeId !== null ? $model::query()->find((int) $typeId) : null;
            $adminType = $type instanceof Blueprint
                ? $resolver->resolveForType($type, NavigationConfiguratorTypeEnum::Navigation, DefaultNavigationConfigurator::getKey())
                : DefaultNavigationConfigurator::class;

            return $adminType::configure($configurator, $context);
        }

        $rawState = $configurator->getRawState();
        $state = $rawState instanceof Arrayable ? $rawState->toArray() : $rawState;
        $typeId = $state['blueprint_id'] ?? null;

        /** @var class-string<Blueprint> $model */
        $model = Blueprint::class;

        $type = $typeId !== null ? $model::query()->find((int) $typeId) : null;
        $adminType = $type instanceof Blueprint
            ? $resolver->resolveForType($type, NavigationConfiguratorTypeEnum::Navigation, DefaultNavigationConfigurator::getKey())
            : DefaultNavigationConfigurator::class;

        return $adminType::configure($configurator, $context);
    }
}
