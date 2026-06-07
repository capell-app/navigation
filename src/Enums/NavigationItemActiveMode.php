<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemActiveMode: string implements HasLabel
{
    case Exact = 'exact';

    case StartsWith = 'starts_with';

    public function getLabel(): string
    {
        return match ($this) {
            self::Exact => __('capell-navigation::generic.active_mode_exact'),
            self::StartsWith => __('capell-navigation::generic.active_mode_starts_with'),
        };
    }
}
