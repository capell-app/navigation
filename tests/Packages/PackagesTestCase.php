<?php

declare(strict_types=1);

namespace Capell\Tests\Packages;

use Capell\Address\Providers\AddressServiceProvider;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\AuthenticationLog\Providers\AuthenticationLogServiceProvider;
use Capell\Blog\Providers\AdminServiceProvider as BlogAdminServiceProvider;
use Capell\Blog\Providers\BlogServiceProvider;
use Capell\Blog\Providers\FrontendServiceProvider as BlogFrontendServiceProvider;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Media;
use Capell\Core\Providers\CapellServiceProvider;
use Capell\DefaultTheme\Providers\DefaultThemeServiceProvider;
use Capell\Events\Providers\AdminServiceProvider as EventsAdminServiceProvider;
use Capell\Events\Providers\EventsServiceProvider;
use Capell\Events\Providers\FrontendServiceProvider as EventsFrontendServiceProvider;
use Capell\Frontend\Contracts\SettingsMigrationProviderInterface;
use Capell\Frontend\Providers\FrontendServiceProvider;
use Capell\Mosaic\Providers\MosaicServiceProvider;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\Tags\Models\Tag;
use Capell\Tags\Providers\TagsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

class PackagesTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerBlazeOptimizedViews();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            resolve(SettingsMigrationProviderInterface::class)->getSettingMigrations(),
            __DIR__ . '/../../vendor/capell-app/frontend/database/settings',
        );
    }

    protected function getPackageServiceName(): string
    {
        return 'capell-packages';
    }

    /**
     * @param  Application  $app
     * @return class-string[]
     */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AddressServiceProvider::class,
            AuthenticationLogServiceProvider::class,
            MosaicServiceProvider::class,
            NavigationServiceProvider::class,
            BlogServiceProvider::class,
            BlogAdminServiceProvider::class,
            BlogFrontendServiceProvider::class,
            EventsServiceProvider::class,
            EventsAdminServiceProvider::class,
            EventsFrontendServiceProvider::class,
            SeoToolsServiceProvider::class,
            TagsServiceProvider::class,
            FrontendServiceProvider::class,
            CapellServiceProvider::class,
            AdminPanelProvider::class,
            AdminServiceProvider::class,
            DefaultThemeServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MosaicServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(SeoToolsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(TagsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(FrontendServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(BlogServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(EventsServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AddressServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(AuthenticationLogServiceProvider::$packageName);

        CapellCore::registerPackage('capell-app/navigation', path: realpath(__DIR__ . '/../../packages/navigation'));
        CapellCore::forcePackageInstalled('capell-app/navigation');

        $app->make(Repository::class)->set('tags.tag_model', Tag::class);
        $app->make(Repository::class)->set('media-library.media_model', Media::class);
    }

    private function registerBlazeOptimizedViews(): void
    {
        foreach ([
            __DIR__ . '/../../packages/blog/resources/views/components',
            __DIR__ . '/../../packages/mosaic/resources/views/components',
            __DIR__ . '/../../packages/seo-tools/resources/views/components/schema',
            __DIR__ . '/../../packages/default-theme/resources/views/components/button/index.blade.php',
            __DIR__ . '/../../packages/themes/default/resources/views/components',
        ] as $path) {
            RegisterBlazeOptimizedViewsAction::run($path);
        }
    }
}
