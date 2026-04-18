<?php

declare(strict_types=1);

namespace Capell\Plugins\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class PluginsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-plugins';

    public static string $packageName = 'capell-app/plugins';

    public static string $description = 'Plugins marketplace for Capell.';

    public function configurePackage(Package $package): void
    {
        $package->name(self::$name)
            ->hasMigrations([
                '2026_01_01_000001_create_marketplace_plugins_table',
                '2026_01_01_000002_create_marketplace_plugin_licenses_table',
                '2026_01_01_000003_create_marketplace_plugin_audit_log_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();
    }

    private function registerPackageMetadata(): void
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            description: static::getDescription(),
            installCommand: 'capell:plugins-install',
        );
    }
}
