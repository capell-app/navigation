<?php

declare(strict_types=1);

arch()
    ->expect('Capell\\Mosaic')
    ->toOnlyBeUsedIn('Capell\\Mosaic');

arch()
    ->expect([
        'Capell\Mosaic',
    ])
    ->classes()
    ->toUseStrictEquality();
