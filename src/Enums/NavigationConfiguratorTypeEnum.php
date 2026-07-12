<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Capell\Admin\Concerns\HasConfiguratorTypes;
use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;
use Capell\Navigation\Filament\Configurators\Navigations\DefaultNavigationConfigurator;

enum NavigationConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    use HasConfiguratorTypes;

    case Navigation = 'Navigations';

    /** @return array<class-string<ConfiguratorInterface>> */
    public function getConfigurators(): array
    {
        return match ($this) {
            self::Navigation => [
                DefaultNavigationConfigurator::class,
            ],
        };
    }
}
