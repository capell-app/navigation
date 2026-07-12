<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationItemType: string implements HasLabel
{
    case Link = 'link';

    case ExternalLink = 'external_link';

    case Page = 'page';

    case Heading = 'heading';

    public function getLabel(): string
    {
        return match ($this) {
            self::Link => __('capell::generic.link'),
            self::ExternalLink => __('capell-navigation::generic.external_link'),
            self::Page => __('capell::generic.page'),
            self::Heading => __('capell::generic.heading'),
        };
    }
}
