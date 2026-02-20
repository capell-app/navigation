<?php

declare(strict_types=1);

namespace Capell\Tests\Address;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Admin\AdminPanelProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class AddressTestCase extends AbstractTestCase
{
    protected string $packageServiceName = 'capell-address';

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
    }
}
