<?php

declare(strict_types=1);

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
    ->laravel()
    ->ignoring([
        'exit',
        'Capell\\Frontend\\Livewire\\Page\\SitemapPage',
    ]);

arch()->preset()->security();

it('does not allow debug functions')
    ->expect(['dd', 'dump', 'print_r', 'die', 'ray', 'rd', 'var_dump'])
    ->toBeUsedInNothing();

arch()->expect(['env', 'sleep', 'usleep'])->toBeUsedInNothing();

arch()
    ->expect([
        'Capell\\Address',
    ])
    ->classes()
    ->toUseStrictEquality();
