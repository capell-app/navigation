<?php

declare(strict_types=1);

namespace Capell\Media\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Media\Enums\ResourceEnum;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! CapellCore::getPackage('capell-app/media')->isInstalled()) {
            return;
        }

        CapellAdmin::registerResource(
            ResourceEnum::Media->name,
            class: ResourceEnum::Media->value,
        );
    }

    public function register(): void
    {
        //
    }
}
