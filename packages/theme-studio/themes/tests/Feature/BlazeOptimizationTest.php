<?php

declare(strict_types=1);

use Capell\Themes\Agency\AgencyThemeServiceProvider;
use Capell\Themes\Corporate\CorporateThemeServiceProvider;
use Capell\Themes\Saas\SaasThemeServiceProvider;
use Livewire\Blaze\Blaze;

it('registers standalone theme component directories with Blaze', function (): void {
    app()->register(AgencyThemeServiceProvider::class);
    app()->register(CorporateThemeServiceProvider::class);
    app()->register(SaasThemeServiceProvider::class);

    $rootPath = dirname(__DIR__, 5);

    expect(Blaze::optimize()->shouldCompile($rootPath . '/packages/theme-studio/themes/agency/resources/views/components/header.blade.php'))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($rootPath . '/packages/theme-studio/themes/corporate/resources/views/components/header.blade.php'))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($rootPath . '/packages/theme-studio/themes/saas/resources/views/components/header.blade.php'))->toBeTrue();
    expect(Blaze::optimize()->shouldCompile($rootPath . '/packages/foundation/themes/default/resources/views/components/header/index.blade.php'))->toBeTrue();
});
