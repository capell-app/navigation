<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Providers;

use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\AuthenticationLogResource;
use Capell\AuthenticationLog\Filament\Widgets\AuthenticationLogsWidget;
use Capell\AuthenticationLog\Http\Middleware\AdminActivityMiddleware;
use Capell\AuthenticationLog\Http\Middleware\UserActivityMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Config::set(
            'filament-authentication-log.resources.AuthenticationLogResource',
            AuthenticationLogResource::class,
        );

        $this->app->bind('capell.authentication-log.admin-middleware', fn (): string => AdminActivityMiddleware::class);
        $this->app->bind('capell.authentication-log.user-middleware', fn (): string => UserActivityMiddleware::class);

        CapellAdmin::registerExtraResource(AuthenticationLogResource::class);
    }

    public function boot(): void
    {
        CapellAdmin::registerDashboardWidget(AuthenticationLogsWidget::class, DashboardEnum::SystemHealth);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('authentication-log:purge')->monthly();
        });
    }
}
