<?php

declare(strict_types=1);

namespace Capell\Themes\Admin\Tests;

use Capell\Tests\Packages\PackagesTestCase;
use Capell\Themes\Admin\ThemesAdminServiceProvider;

abstract class ThemesAdminTestCase extends PackagesTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'themes-admin';
    }

    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ThemesAdminServiceProvider::class,
        ];
    }
}
