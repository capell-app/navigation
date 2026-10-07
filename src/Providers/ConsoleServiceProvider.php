<?php

declare(strict_types=1);

namespace Capell\Navigation\Providers;

use Capell\Navigation\Console\Commands\DemoCommand;
use Capell\Navigation\Console\Commands\SetupCommand;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Override;

final class ConsoleServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([DemoCommand::class, SetupCommand::class]);

        // Composer can add this provider to an already running installer.
        if ($this->app instanceof Application && $this->app->isBooted()) {
            Artisan::registerCommand($this->app->make(DemoCommand::class));
            Artisan::registerCommand($this->app->make(SetupCommand::class));
        }
    }
}
