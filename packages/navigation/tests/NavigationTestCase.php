<?php

declare(strict_types=1);

namespace Capell\Navigation\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Tests\AbstractTestCase;
use Override;

class NavigationTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-navigation';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            NavigationServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(NavigationServiceProvider::$packageName);
    }
}
