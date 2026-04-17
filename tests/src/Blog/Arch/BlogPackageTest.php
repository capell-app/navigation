<?php

declare(strict_types=1);

use Capell\Admin\Console\Commands\DemoCommand;
use Capell\Admin\Console\Commands\InstallCommand;
use Capell\Core\Database\Factories\TypeFactory;
use Capell\Core\Support\Creator\DemoCreator;
use Capell\Mosaic\Console\Commands\HeroDemoCommand;

arch()
    ->expect('Capell\Blog')
    ->toOnlyBeUsedIn('Capell\Blog')
    ->ignoring([
        InstallCommand::class,
        DemoCommand::class,
        DemoCreator::class,
        TypeFactory::class,
        HeroDemoCommand::class,
    ]);

arch()
    ->expect([
        'Capell\Blog',
    ])
    ->classes()
    ->toUseStrictEquality();
