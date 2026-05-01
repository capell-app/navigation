<?php

declare(strict_types=1);

use Capell\Admin\Enums\ResourceEnum as AdminResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Events\Enums\ResourceEnum;
use Capell\Events\Filament\Resources\Events\EventResource;
use Capell\Events\Providers\EventsServiceProvider;

it('registers the event resource when the package is installed', function (): void {
    CapellCore::forcePackageInstalled(EventsServiceProvider::$packageName);

    expect(CapellAdmin::getResource(AdminResourceEnum::Page, 'event'))->toBe(EventResource::class)
        ->and(ResourceEnum::Event->value)->toBe(EventResource::class);
});
