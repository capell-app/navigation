<?php

declare(strict_types=1);

namespace Capell\Events\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Events\Providers\AdminServiceProvider;
use Capell\Events\Providers\ConsoleServiceProvider;
use Capell\Events\Providers\EventsServiceProvider;
use Capell\Events\Providers\FrontendServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

class EventsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-events';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            EventsServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            ConsoleServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(EventsServiceProvider::$packageName);
    }
}
