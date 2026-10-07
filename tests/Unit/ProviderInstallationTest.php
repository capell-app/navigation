<?php

declare(strict_types=1);

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Tests\Support\PackageInstallationTestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

it('activates installed runtime once after metadata has already booted', function (): void {
    PackageInstallationTestCase::assertInProcessInstallation('navigation', function (Application $app, Closure $refresh): void {
        $finder = $app->make(Factory::class)->getFinder();
        throw_unless($finder instanceof FileViewFinder, RuntimeException::class);
        expect($finder->getHints())->not->toHaveKey('capell-navigation');
        $refresh();
        expect($finder->getHints())->toHaveKey('capell-navigation')
            ->and(AdminSurfaceLookup::resource('Navigation'))->toBe(NavigationResource::class);

        $schedule = $app->make(Schedule::class);
        $scheduledEvents = $schedule->events();
        $listeners = $app->make(Dispatcher::class)->getRawListeners();
        $views = $finder->getHints();
        $refresh();
        expect($app->make(Dispatcher::class)->getRawListeners())->toBe($listeners)
            ->and($finder->getHints())->toBe($views)
            ->and($schedule->events())->toBe($scheduledEvents);
    });
});
