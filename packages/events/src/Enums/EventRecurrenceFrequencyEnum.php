<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Filament\Support\Contracts\HasLabel;

enum EventRecurrenceFrequencyEnum: string implements HasLabel
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => __('capell-events::generic.recurrence.none'),
            self::Daily => __('capell-events::generic.recurrence.daily'),
            self::Weekly => __('capell-events::generic.recurrence.weekly'),
            self::Monthly => __('capell-events::generic.recurrence.monthly'),
        };
    }
}
