<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Events\Enums\LivewirePageComponentEnum;
use Capell\Events\Models\Event;
use Capell\Events\Support\EventsModelRegistrar;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class EventsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-events';

    public static string $packageName = 'capell-app/events';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasRoute('web');
    }

    public function registeringPackage(): void
    {
        $this
            ->registerPackageMetadata()
            ->registerPackageAssets();

        $this->booted(function (): void {
            if (! CapellCore::getPackage(static::$packageName)->isInstalled()) {
                return;
            }

            EventsModelRegistrar::register();
            $this->registerBladeComponents();
            $this->registerLivewireComponents();
            $this->registerTypes();
        });
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Events\\View\\Components', 'capell-events');

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        foreach (LivewirePageComponentEnum::getComponents() as $name => $component) {
            if (! $component) {
                continue;
            }

            Livewire::component($name, $component);
        }

        return $this;
    }

    private function registerTypes(): self
    {
        CapellCore::registerPageType(
            new PageTypeData(
                name: 'event',
                model: Event::class,
                label: fn (): string => __('capell-events::generic.event'),
            ),
        );

        return $this;
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            permissions: [
                'create_event',
                'replicate_event',
                'restore_any_event',
                'restore_event',
                'update_event',
                'view_any_event',
                'view_event',
            ],
            description: fn (): string => __('capell-events::package.description'),
        );

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }
}
