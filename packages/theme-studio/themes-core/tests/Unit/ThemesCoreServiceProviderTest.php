<?php

declare(strict_types=1);

use Capell\Themes\Core\ThemesCoreServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Livewire\Blaze\Blaze;

it('registers themes-core views and components with Blaze', function (): void {
    app()->register(ThemesCoreServiceProvider::class);

    expect(view()->exists('capell-themes-core::components.mobile-menu'))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile(dirname(__DIR__, 2) . '/resources/views/components/mobile-menu.blade.php'))->toBeTrue();
});

it('registers themes-core console commands', function (): void {
    app()->register(ThemesCoreServiceProvider::class);

    expect(Artisan::all())->toHaveKeys([
        'themes:preview-token',
        'themes:sitemap',
    ]);
});
