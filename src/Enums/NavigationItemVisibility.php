<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemVisibility: string implements HasLabel
{
    case Everyone = 'everyone';

    case Guests = 'guests';

    case Authenticated = 'authenticated';

    case Ability = 'ability';

    case Role = 'role';

    public function getLabel(): string
    {
        return match ($this) {
            self::Everyone => __('capell-navigation::generic.visibility_everyone'),
            self::Guests => __('capell-navigation::generic.visibility_guests'),
            self::Authenticated => __('capell-navigation::generic.visibility_authenticated'),
            self::Ability => __('capell-navigation::generic.visibility_ability'),
            self::Role => __('capell-navigation::generic.visibility_role'),
        };
    }
}
