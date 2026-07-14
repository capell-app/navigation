<?php

declare(strict_types=1);

use Capell\Core\Enums\VendorAssetEnum;
use Capell\Core\Facades\CapellCore;

it('registers navigation views as Tailwind build sources', function (): void {
    $sources = CapellCore::getVendorAssetsForType(VendorAssetEnum::TailwindSource)
        ->filter(fn (object $asset): bool => $asset->packageName === 'capell-app/navigation')
        ->pluck('value')
        ->all();

    expect($sources)->toContain('resources/views/**/*.blade.php');
});
