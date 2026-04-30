<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Tests;

use Capell\Core\Facades\CapellCore;
use Capell\FilamentPeek\Providers\FilamentPeekServiceProvider;
use Capell\Tests\AbstractTestCase;
use Livewire\LivewireServiceProvider;
use Override;

abstract class FilamentPeekTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-filament-peek';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentPeekServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(FilamentPeekServiceProvider::$packageName);
    }
}
