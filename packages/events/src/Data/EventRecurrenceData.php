<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Capell\Events\Enums\EventRecurrenceFrequencyEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class EventRecurrenceData extends Data
{
    /**
     * @param  array<int, string>  $weekdays
     */
    public function __construct(
        public EventRecurrenceFrequencyEnum $frequency = EventRecurrenceFrequencyEnum::None,
        public int $interval = 1,
        public array $weekdays = [],
        public ?int $monthDay = null,
        public ?string $until = null,
        public ?int $count = null,
    ) {}
}
