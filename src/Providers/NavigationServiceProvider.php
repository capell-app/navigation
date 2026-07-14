<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Data\PageTypeData;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Events\PageUrlChanged;
use Capell\Core\Events\SiteReplicated;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\Core\Support\Packages\PackageSurfaceRegistrar;
use Capell\Frontend\Contracts\FrontendRuntimeManifestContributor;
use Capell\Frontend\Data\RenderHookContext;
use Capell\Frontend\Data\RenderHookContributionData;
use Capell\Frontend\Enums\CacheEnum as FrontendCacheEnum;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\Render\FrontendHookRegistrar;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Adapters\NavigationNamesResolverAdapter;
use Capell\Navigation\Adapters\NavigationPageSyncerAdapter;
use Capell\Navigation\Console\Commands\DemoCommand;
use Capell\Navigation\Console\Commands\SetupCommand;
use Capell\Navigation\Contracts\NavigationNamesResolver;
use Capell\Navigation\Contracts\NavigationPageSyncer;
use Capell\Navigation\Enums\NavigationConfiguratorTypeEnum;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Listeners\ReplicateSiteNavigationsListener;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Capell\Navigation\Support\ContentGraph\NavigationContentGraphExtractor;
use Capell\Navigation\Support\NavigationFrontendRuntimeManifestContributor;
use Capell\Navigation\Support\NavigationNamesResolver as ConcreteNavigationNamesResolver;
use Capell\Navigation\Support\RenderHooks\RegisterFoundationHeaderNavigationHook;
use Capell\Navigation\View\Composers\NavigationRenderModelComposer;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Override;

final class NavigationServiceProvider extends ServiceProvider
{
    private const string EventListenersRegisteredFlag = 'capell.navigation.event-listeners-registered';

    public static string $packageName = 'capell-app/navigation';

    private bool $installedPackageRegistered = false;

    #[Override]
    public function register(): void
    {
        $this->registerContentGraphExtractors();
        $this->commands([DemoCommand::class, SetupCommand::class]);

        $this->app->booting(function (): void {
            if ($this->isPackageInstalled()) {
                $this->registerResources();
            }
        });

        $this->app->booted(function (): void {
            if ($this->isPackageInstalled()) {
                $this->registerInstalledPackage();
            }
        });
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->registerInstalledPackage();
    }

    protected function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerInstalledPackage(): void
    {
        if ($this->installedPackageRegistered) {
            return;
        }

        $this->installedPackageRegistered = true;

        $this
            ->registerServices()
            ->registerRoutes()
            ->registerSchemaExtenders()
            ->registerResources()
            ->registerPageTypes()
            ->registerModels()
            ->registerConfigurators()
            ->registerFrontendRenderHooks(force: true)
            ->registerPackageAssets()
            ->registerBladeComponents()
            ->registerFrontendRuntimeManifestContributors()
            ->registerPolicies()
            ->registerRelationships()
            ->registerEventListeners();
    }

    private function registerServices(): self
    {
        $this->app->singleton(NavigationPageSyncer::class, NavigationPageSyncerAdapter::class);
        $this->app->singleton(NavigationNamesResolver::class, NavigationNamesResolverAdapter::class);
        $this->app->singleton(
            ConcreteNavigationNamesResolver::class,
            fn (Application $app): ConcreteNavigationNamesResolver => new ConcreteNavigationNamesResolver(
                $app->make(Factory::class)->store(),
            ),
        );

        return $this;
    }

    private function registerRoutes(): self
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

        return $this;
    }

    private function registerSchemaExtenders(): self
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);

        return $this;
    }

    private function registerResources(): self
    {
        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: NavigationResource::class,
            group: 'Navigation',
        ));

        return $this;
    }

    private function registerPageTypes(): self
    {
        $type = new PageTypeData(
            name: 'navigation',
            model: Navigation::class,
            label: 'Navigation',
        );

        app(PackageSurfaceRegistrar::class)->pageType($type);
        CapellCore::registerPageType($type);

        return $this;
    }

    private function registerModels(): self
    {
        app(PackageSurfaceRegistrar::class)->models([Navigation::class]);
        CapellCore::registerModels([Navigation::class]);

        return $this;
    }

    private function registerConfigurators(): self
    {
        foreach (NavigationConfiguratorTypeEnum::getAllConfigurators() as $type => $configurators) {
            foreach ($configurators as $configuratorClass) {
                CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::configurator(
                    class: $configuratorClass,
                    group: $type,
                    name: $configuratorClass::getKey(),
                ));
            }
        }

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'capell-navigation');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-navigation');
        View::composer([
            'capell-navigation::components.breadcrumbs',
            'capell-navigation::components.header.navigation',
            'capell-navigation::components.menu',
        ], NavigationRenderModelComposer::class);

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\Navigation\\View\\Components', 'capell-navigation');

        return $this;
    }

    private function registerFrontendRuntimeManifestContributors(): self
    {
        $this->app->tag([NavigationFrontendRuntimeManifestContributor::class], FrontendRuntimeManifestContributor::TAG);

        return $this;
    }

    private function registerFrontendRenderHooks(bool $force = false): self
    {
        $this->app->afterResolving(
            RenderHookRegistry::class,
            fn (RenderHookRegistry $registry): mixed => $this->registerFrontendRenderHooksForRegistry($registry, $force),
        );

        if (! $force && ! $this->isPackageInstalled()) {
            return $this;
        }

        if ($force && ! $this->app->bound(RenderHookRegistry::class)) {
            $this->app->singleton(RenderHookRegistry::class);
        }

        if ($this->app->bound(RenderHookRegistry::class)) {
            $this->registerFrontendRenderHooksForRegistry($this->app->make(RenderHookRegistry::class), $force);
        }

        if (! $this->app->bound(FrontendHookRegistrar::class)) {
            return $this;
        }

        $registrar = $this->app->make(FrontendHookRegistrar::class);
        $hook = new RegisterFoundationHeaderNavigationHook;

        $registrar->contribute(
            location: RenderHookLocation::HeaderAfter,
            extension: $hook,
            owner: self::$packageName,
            key: 'foundation-header-navigation-default',
            scenario: RegisterFoundationHeaderNavigationHook::DefaultScenario,
            target: RegisterFoundationHeaderNavigationHook::Target,
            cacheSafe: true,
        );

        $registrar->contribute(
            location: RenderHookLocation::HeaderAfter,
            extension: $hook,
            owner: self::$packageName,
            key: 'foundation-header-navigation-foundation',
            scenario: RegisterFoundationHeaderNavigationHook::FoundationScenario,
            target: RegisterFoundationHeaderNavigationHook::Target,
            cacheSafe: true,
        );

        return $this;
    }

    /**
     * @param  RenderHookRegistry<RenderHookContext>  $registry
     */
    private function registerFrontendRenderHooksForRegistry(RenderHookRegistry $registry, bool $force = false): self
    {
        if (! $force && ! $this->isPackageInstalled()) {
            return $this;
        }

        $hook = new RegisterFoundationHeaderNavigationHook;

        $registry->contribute(RenderHookContributionData::extension(
            location: RenderHookLocation::HeaderAfter,
            extension: $hook,
            owner: self::$packageName,
            key: 'foundation-header-navigation-default',
            scenario: RegisterFoundationHeaderNavigationHook::DefaultScenario,
            target: RegisterFoundationHeaderNavigationHook::Target,
            cacheSafe: true,
        ));

        $registry->contribute(RenderHookContributionData::extension(
            location: RenderHookLocation::HeaderAfter,
            extension: $hook,
            owner: self::$packageName,
            key: 'foundation-header-navigation-foundation',
            scenario: RegisterFoundationHeaderNavigationHook::FoundationScenario,
            target: RegisterFoundationHeaderNavigationHook::Target,
            cacheSafe: true,
        ));

        return $this;
    }

    private function registerPolicies(): self
    {
        Gate::policy(Navigation::class, NavigationPolicy::class);

        return $this;
    }

    private function registerRelationships(): self
    {
        Site::resolveRelationUsing('navigations', fn (Site $site): HasMany => $site->hasMany(Navigation::class));

        return $this;
    }

    private function registerEventListeners(): self
    {
        if ($this->app->bound(self::EventListenersRegisteredFlag)) {
            return $this;
        }

        $this->app->instance(self::EventListenersRegisteredFlag, true);

        Event::listen(SiteReplicated::class, ReplicateSiteNavigationsListener::class);
        Event::listen(PageUrlChanged::class, $this->handlePageUrlChanged(...));

        return $this;
    }

    private function registerContentGraphExtractors(): self
    {
        if (class_exists(ContentGraphRegistry::class)) {
            $this->app->singleton(NavigationContentGraphExtractor::class);
            $this->app->tag(NavigationContentGraphExtractor::class, ContentGraphRegistry::TAG);
        }

        return $this;
    }

    private function handlePageUrlChanged(PageUrlChanged $event): void
    {
        BuildNavigationRenderModelAction::flushPageCache();
        BuildNavigationRenderModelAction::flushSharedRenderModelCache();

        CapellCore::removeCacheKey(FrontendCacheEnum::Navigations->value);
        CapellCore::removeCacheKey(FrontendCacheEnum::siteNavigations($event->site_id));

        $navigations = Navigation::query()
            ->where(function (Builder $query) use ($event): void {
                $query
                    ->where('site_id', $event->site_id)
                    ->orWhereNull('site_id');
            })
            ->where(function (Builder $query) use ($event): void {
                $query
                    ->where('language_id', $event->language_id)
                    ->orWhereNull('language_id');
            })
            ->get(['id', 'key', 'site_id', 'language_id']);

        foreach ($navigations as $navigation) {
            CapellCore::removeCacheKey(FrontendCacheEnum::navigationById((int) $navigation->getKey()));

            if ($navigation->site_id !== null) {
                CapellCore::removeCacheKey(FrontendCacheEnum::navigation(
                    $navigation->key,
                    $navigation->site_id,
                    $navigation->language_id,
                ));
            }
        }
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }
}
