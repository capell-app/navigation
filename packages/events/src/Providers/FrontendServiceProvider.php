<?php

declare(strict_types=1);

namespace Capell\Events\Providers;

use Capell\Events\Support\RenderHooks\RegisterEventSchemaHook;
use Illuminate\Support\ServiceProvider;

final class FrontendServiceProvider extends ServiceProvider
{
    private bool $renderHooksRegistered = false;

    public function register(): void {}

    public function boot(): void
    {
        $this->registerRenderHooks();

        $this->app->booted(function (): void {
            $this->registerRenderHooks();
        });
    }

    private function registerRenderHooks(): void
    {
        if ($this->renderHooksRegistered) {
            return;
        }

        $this->app->make(RegisterEventSchemaHook::class)->register();
        $this->renderHooksRegistered = true;
    }
}
