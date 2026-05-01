<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class EventCalendarDayData extends Data
{
    public function __construct(
        public CarbonImmutable $date,
        public bool $isCurrentMonth,
        public bool $isToday,
        public bool $isSelected,
        public int $occurrenceCount,
    ) {}
}
