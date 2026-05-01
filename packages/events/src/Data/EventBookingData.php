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
class EventBookingData extends Data
{
    public function __construct(
        public ?string $url = null,
        public ?string $label = null,
        #[WithCast(DateTimeInterfaceCast::class, 'Y-m-d H:i:s')]
        public ?CarbonImmutable $opensAt = null,
        #[WithCast(DateTimeInterfaceCast::class, 'Y-m-d H:i:s')]
        public ?CarbonImmutable $closesAt = null,
    ) {}
}
