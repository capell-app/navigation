<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

use Filament\Support\Contracts\HasLabel;
use Override;

enum NavigationItemType: string implements HasLabel
{
    case Link = 'link';

    case ExternalLink = 'external_link';

    case Page = 'page';

    case Heading = 'heading';

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::Link => __('capell-admin::generic.link'),
            self::ExternalLink => __('capell-navigation::generic.external_link'),
            self::Page => __('capell-admin::generic.page'),
            self::Heading => __('capell-admin::form.heading'),
        };
    }
}
