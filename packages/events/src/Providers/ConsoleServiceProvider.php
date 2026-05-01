<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Events\Console\Commands\InstallCommand;
use Capell\Events\Console\Commands\SetupCommand;
use Illuminate\Support\ServiceProvider;

final class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SetupCommand::class,
            ]);
        }
    }
}
