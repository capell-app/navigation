<?php

declare(strict_types=1);

namespace Capell\FilamentPeek\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\FilamentPeek\Filament\Extenders\FilamentPeekAdminPanelExtender;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([FilamentPeekAdminPanelExtender::class], AdminPanelExtender::TAG);
    }
}
