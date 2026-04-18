<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Plugins\Providers\PluginsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Admin\AdminPanelProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Override;

class PluginsTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-plugins';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PluginsServiceProvider::class,
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
        CapellCore::forcePackageInstalled(PluginsServiceProvider::$packageName);
    }
}
