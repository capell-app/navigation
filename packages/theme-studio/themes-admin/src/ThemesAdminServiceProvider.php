<?php

declare(strict_types=1);

namespace Capell\Themes\Admin;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Themes\Admin\Schemas\ThemeSettingsSchema;
use Capell\Themes\Admin\Settings\ThemeAdminSettings;
use Spatie\LaravelPackageTools\Package;

final class ThemesAdminServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-themes-admin';

    public static string $packageName = 'capell-app/themes-admin';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerSettings();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            setting: ThemeAdminSettings::class,
            description: fn (): string => __('capell-themes-admin::package.description'),
        );

        return $this;
    }

    private function registerSettings(): self
    {
        $this->app->afterResolving(
            SettingsSchemaRegistry::class,
            function (SettingsSchemaRegistry $registry): void {
                $registry->registerSettingsClass(ThemeAdminSettings::group(), ThemeAdminSettings::class);
                $registry->register(ThemeAdminSettings::group(), ThemeSettingsSchema::class);
            },
        );

        return $this;
    }
}
