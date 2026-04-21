<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\SystemHealthPage;

arch()
    ->expect('Capell\\Assistant')
    ->toOnlyBeUsedIn('Capell\\Assistant')
    ->ignoring([
        SystemHealthPage::class,
    ]);

arch()
    ->expect('Capell\Assistant')
    ->classes()
    ->toUseStrictEquality();
