<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventLocationTypeEnum: string implements HasLabel
{
    case Physical = 'physical';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Physical => __('capell-events::generic.location_type.physical'),
            self::Online => __('capell-events::generic.location_type.online'),
            self::Hybrid => __('capell-events::generic.location_type.hybrid'),
        };
    }
}
