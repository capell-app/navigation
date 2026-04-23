<?php

declare(strict_types=1);

namespace Capell\DefaultTheme\Providers;

use Capell\Core\Data\VendorAssetData;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\DefaultTheme\Support\Interceptors\Themes\DefaultThemeInterceptor;
use Illuminate\Support\ServiceProvider;

class DefaultThemeServiceProvider extends ServiceProvider
{
    private static string $packageName = 'capell-app/default-theme';

    public function boot(): void
    {
        $themeModel = CapellCore::getModel(ModelEnum::Theme);
        CapellCore::registerModelInterceptor($themeModel, interceptorClass: DefaultThemeInterceptor::class);

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindImport('resources/css/default-theme.css', self::$packageName),
        );

        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', self::$packageName),
        );
    }

    public function register(): void {}
}
