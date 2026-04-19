<?php

declare(strict_types=1);

namespace Capell\Themes\Admin;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemesAdminServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('themes-admin');
    }
}
