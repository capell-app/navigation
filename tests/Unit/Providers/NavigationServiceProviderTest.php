<?php

declare(strict_types=1);

use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Providers\NavigationServiceProvider;

it('registers navigation package metadata for install workflows', function (): void {
    $package = CapellCore::getPackage(NavigationServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/navigation')
        ->and($package->serviceProviderClass)->toBe(NavigationServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe('Site- and language-scoped navigation menus for Capell: visual menu builder, page & link items, nested dropdowns, active-state rendering, publish scheduling, and multi-site replication.')
        ->and($package->getSetupCommand())->toBe('capell:navigation-setup')
        ->and($package->getSetupParams())->toBe(['sites']);
});

it('registers navigation views as frontend tailwind sources', function (): void {
    expect(CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource)
        ->where('packageName', NavigationServiceProvider::$packageName)
        ->pluck('value')
        ->all())->toContain('resources/views/**/*.blade.php');
});
