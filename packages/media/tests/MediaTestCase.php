<?php

declare(strict_types=1);

namespace Capell\Media\Tests;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Media\Models\Media;
use Capell\Media\Providers\AdminServiceProvider as MediaAdminServiceProvider;
use Capell\Media\Providers\MediaServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

class MediaTestCase extends AbstractTestCase
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
        return 'capell-media';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            MediaServiceProvider::class,
            AdminServiceProvider::class,
            AdminPanelProvider::class,
            MediaAdminServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(MediaServiceProvider::$packageName);

        $app->make('config')->set('media-library.media_model', Media::class);
    }
}
