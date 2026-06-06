<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationDropdownLayout: string implements HasLabel
{
    case Dropdown = 'dropdown';

    case Mega = 'mega';

    public function getLabel(): string
    {
        return match ($this) {
            self::Dropdown => __('capell-navigation::generic.dropdown_layout_dropdown'),
            self::Mega => __('capell-navigation::generic.dropdown_layout_mega'),
        };
    }
}
