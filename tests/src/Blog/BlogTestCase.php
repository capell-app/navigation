<?php

declare(strict_types=1);

namespace Capell\Tests\Blog;

use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Support\CapellAdminManager;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\CapellCoreManager;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Layout\Providers\LayoutServiceProvider;
use Capell\Tests\AbstractTestCase;
use Capell\Tests\Fixtures\Support\Filament\AdminPanelProvider;
use Livewire\LivewireServiceProvider;
use Override;

class BlogTestCase extends AbstractTestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAndMigrateSettings([
            ...CapellCoreManager::getSettingMigrations(),
        ], __DIR__ . '/../../../vendor/capell-app/core/database/settings');

        $this->registerAndMigrateSettings([
            ...CapellAdminManager::getSettingMigrations(),
        ], __DIR__ . '/../../../vendor/capell-app/admin/database/settings');

        $this->registerAndMigrateSettings([
            ...resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
        ], __DIR__ . '/../../../vendor/capell-app/frontend/database/settings');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            LayoutServiceProvider::class,
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            BlogServiceProvider::class,
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(LayoutServiceProvider::$packageName);
    }

    protected function requiredPackages(): array
    {
        return ['layout', 'blog', 'frontend', 'admin'];
    }
}
