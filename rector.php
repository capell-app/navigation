<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/packages',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/packages/layout/src/LayoutServiceProvider.php', // Renaming blade component to aliasComponent https://github.com/driftingly/rector-laravel/issues/356
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        rectorPreset: true,
        instanceOf: true,
        carbon: true,
        phpunitCodeQuality: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withPhpSets(php84: true)
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_120,
    ]);
