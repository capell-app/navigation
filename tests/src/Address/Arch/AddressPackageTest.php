<?php

declare(strict_types=1);

use Capell\Frontend\Http\Middleware\ResolveFrontend;

arch()
    ->expect('Capell\\Address')
    ->toOnlyBeUsedIn('Capell\\Address');

arch()
    ->preset()
    ->php()
    ->ignoring([
        'var_export',
        'Capell\\Core',
    ]);

arch()
    ->preset()
    ->laravel();

arch()->preset()->security()
    ->ignoring([
        ResolveFrontend::class,
    ]);

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing();

arch()->expect(['env', 'sleep', 'usleep'])->toBeUsedInNothing()->ignoring([
    \Capell\Blog\Console\Commands\InstallCommand::class,
]);

arch()
    ->expect([
        'Capell\\Address',
    ])
    ->classes()
    ->toUseStrictEquality();
