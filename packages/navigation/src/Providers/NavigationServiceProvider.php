<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Admin\Enums\SchemaExtenderEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Capell\Navigation\Filament\Extenders\NavigationSiteExtender;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Policies\NavigationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSchemaExtender(SchemaExtenderEnum::Page->value, NavigationPageSchemaExtender::class);
        $this->registerSchemaExtender(SchemaExtenderEnum::Site->value, NavigationSiteExtender::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'capell-navigation');

        Gate::policy(Navigation::class, NavigationPolicy::class);

        CapellAdmin::registerResource('Navigation', NavigationResource::class);
    }

    private function registerSchemaExtender(string $tag, string $class): void
    {
        $this->app->singleton($class, fn (): object => new $class);
        $this->app->tag($class, $tag);
    }
}
