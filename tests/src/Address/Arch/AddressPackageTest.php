<?php

declare(strict_types=1);

use Capell\Frontend\Http\Middleware\ResolveFrontend;
use Capell\Frontend\Livewire\Page\SitemapPage;

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
        SitemapPage::class,
    ]);

arch()->preset()->security()
    ->ignoring([
        ResolveFrontend::class,
    ]);

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
