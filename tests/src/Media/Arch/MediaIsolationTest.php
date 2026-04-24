<?php

declare(strict_types=1);

arch()
    ->expect('Capell\Media')
    ->toOnlyBeUsedIn('Capell\Media');

arch()
    ->expect('Capell\Media')
    ->classes()
    ->toUseStrictEquality();
