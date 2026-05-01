<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class EventScheduleData extends Data
{
    public function __construct(
        #[WithCast(DateTimeInterfaceCast::class, 'Y-m-d H:i:s')]
        public CarbonImmutable $startsAt,
        #[WithCast(DateTimeInterfaceCast::class, 'Y-m-d H:i:s')]
        public ?CarbonImmutable $endsAt = null,
        public string $timezone = 'UTC',
        public ?EventRecurrenceData $recurrence = null,
        #[WithCast(DateTimeInterfaceCast::class, 'Y-m-d')]
        public ?CarbonImmutable $generateUntil = null,
    ) {
        $this->recurrence ??= new EventRecurrenceData;
    }
}
