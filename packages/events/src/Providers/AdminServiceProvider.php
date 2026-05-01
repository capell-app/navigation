<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Admin\Enums\ConfiguratorTypeEnum;
use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Configurators\Events\EventPageConfigurator;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerResources();
    }

    public function boot(): void
    {
        if (! CapellCore::getPackage(EventsServiceProvider::$packageName)->isInstalled()) {
            return;
        }

        CapellAdmin::registerConfigurator(ConfiguratorTypeEnum::Page, EventPageConfigurator::class);
    }

    private function registerResources(): void
    {
        CapellAdmin::registerResource(
            AdminResourceEnum::Page,
            class: ResourceEnum::Event->value,
            name: strtolower(ResourceEnum::Event->name),
        );
    }
}
