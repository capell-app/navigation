<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemTarget: string implements HasLabel
{
    case Self = '_self';

    case Blank = '_blank';

    case Parent = '_parent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Self => __('capell-navigation::generic.target_self'),
            self::Blank => __('capell-admin::generic.new_tab'),
            self::Parent => __('capell-navigation::generic.target_parent'),
        };
    }
}
