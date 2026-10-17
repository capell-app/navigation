<?php

declare(strict_types=1);

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Actions\DisablePackageAction;
use Capell\Core\Actions\EnablePackageAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\InstalledRuntimeLifecycle;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Capell\Tests\Support\PackageInstallationSurfaceSnapshot;
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

it('preserves installed surfaces after repeated provider registration', function (): void {
    PackageInstallationTestCase::assertInProcessInstallation('navigation', function (Application $app, Closure $refresh): void {
        $refresh();
        $installed = PackageInstallationSurfaceSnapshot::capture($app);

        for ($registration = 0; $registration < 2; $registration++) {
            $app->register(NavigationServiceProvider::class, force: true);
            $app->make(InstalledRuntimeLifecycle::class)->refresh(NavigationServiceProvider::$packageName);
            $actual = PackageInstallationSurfaceSnapshot::capture($app);
            foreach ($installed as $surface => $expected) {
                expect($actual[$surface])->toBe($expected, $surface);
            }
        }
    });
});

it('preserves installed surfaces through disable and enable cycles', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('navigation'), true, sourceRoot: dirname(__DIR__, 2));
        $installed = PackageInstallationSurfaceSnapshot::capture($app);
        $package = CapellCore::getPackage(NavigationServiceProvider::$packageName);

        for ($cycle = 0; $cycle < 2; $cycle++) {
            DisablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeFalse();
            $app->make(InstalledRuntimeLifecycle::class)->refresh($package->name);
            EnablePackageAction::run($package);
            expect(CapellCore::isPackageEnabled($package->name))->toBeTrue();
            $actual = PackageInstallationSurfaceSnapshot::capture($app);
            foreach ($installed as $surface => $expected) {
                expect($actual[$surface])->toBe($expected, $surface);
            }
        }
    });
});
