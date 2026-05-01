<?php

declare(strict_types=1);

namespace Capell\Events\Data;

use Spatie\LaravelData\Data;

class EventSchemaData extends Data
{
    public function __construct(
        public ?string $organizer = null,
        public ?string $performer = null,
    ) {}
}
