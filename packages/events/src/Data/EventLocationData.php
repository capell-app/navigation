<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Capell\Events\Enums\EventLocationTypeEnum;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class EventLocationData extends Data
{
    public function __construct(
        public EventLocationTypeEnum $type = EventLocationTypeEnum::Physical,
        public ?string $name = null,
        public ?string $address = null,
        public ?string $url = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}
}
