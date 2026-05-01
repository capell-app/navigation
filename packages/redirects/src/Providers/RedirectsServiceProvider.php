<?php

declare(strict_types=1);

namespace Capell\Redirects\Providers;

use Capell\Admin\Support\CapellAdminManager;
use Capell\Core\Events\PageSaved;
use Capell\Core\Models\PageUrl;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Redirects\Contracts\BrokenUrlReporter;
use Capell\Redirects\Contracts\RedirectRecorder;
use Capell\Redirects\Contracts\RedirectResolver;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Capell\Redirects\Listeners\CreateRedirectsForChangedPageUrls;
use Capell\Redirects\Policies\RedirectPolicy;
use Capell\Redirects\Support\DatabaseBrokenUrlReporter;
use Capell\Redirects\Support\PageUrlRedirectRecorder;
use Capell\Redirects\Support\PageUrlRedirectResolver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;

class RedirectsServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'redirects';

    public static string $packageName = 'capell-app/redirects';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('redirects')
            ->hasMigration('create_broken_links_table')
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RedirectResolver::class, PageUrlRedirectResolver::class);
        $this->app->singleton(RedirectRecorder::class, PageUrlRedirectRecorder::class);
        $this->app->singleton(BrokenUrlReporter::class, DatabaseBrokenUrlReporter::class);

        Event::listen(PageSaved::class, [CreateRedirectsForChangedPageUrls::class, 'handle']);

        if (class_exists(CapellAdminManager::class)) {
            Gate::policy(PageUrl::class, RedirectPolicy::class);

            $registerRedirectResource = static function (CapellAdminManager $capellAdminManager): void {
                if (! $capellAdminManager->hasResource('Redirect')) {
                    $capellAdminManager->registerResource('Redirect', RedirectResource::class);
                }
            };

            $this->app->afterResolving(CapellAdminManager::class, $registerRedirectResource);

            if ($this->app->resolved(CapellAdminManager::class)) {
                $registerRedirectResource($this->app->make(CapellAdminManager::class));
            }
        }
    }
}
