<?php

declare(strict_types=1);

namespace Capell\SeoTools\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\SeoTools\Providers\SeoToolsServiceProvider;
use Capell\Tests\AbstractTestCase;
use Override;

class SeoToolsTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-seo-tools';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            SeoToolsServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(SeoToolsServiceProvider::$packageName);
    }
}
