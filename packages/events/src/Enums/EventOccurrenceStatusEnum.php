<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventOccurrenceStatusEnum: string implements HasLabel
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scheduled => __('capell-events::generic.occurrence_status.scheduled'),
            self::Cancelled => __('capell-events::generic.occurrence_status.cancelled'),
            self::Postponed => __('capell-events::generic.occurrence_status.postponed'),
        };
    }
}
