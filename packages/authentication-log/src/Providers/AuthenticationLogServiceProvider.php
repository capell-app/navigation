<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Providers;

use Capell\AuthenticationLog\Models\AuthenticationLog;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AuthenticationLogServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-authentication-log';

    public static string $packageName = 'capell-app/authentication-log';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('authentication-log')
            ->hasMigrations([
                'add_last_seen_at_to_authentication_log_table',
                'add_authenticatable_login_at_authentication_log_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata()
            ->registerModels();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-authentication-log::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([AuthenticationLog::class]);

        return $this;
    }
}
