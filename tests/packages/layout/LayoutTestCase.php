<?php

declare(strict_types=1);

namespace Capell\Tests\Layout;

use Capell\Admin\AdminServiceProvider;
use Capell\Frontend\FrontendServiceProvider;
use Capell\Layout\CapellLayoutManager;
use Capell\Layout\LayoutServiceProvider;
use Capell\Tests\packages\AbstractTestCase;
use Capell\Tests\Support\Filament\AdminPanelProvider;

class LayoutTestCase extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageMigrations(CapellLayoutManager::getMigrations());
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AdminServiceProvider::class,
            FrontendServiceProvider::class,
            AdminPanelProvider::class,
            LayoutServiceProvider::class,
        ];
    }
}
