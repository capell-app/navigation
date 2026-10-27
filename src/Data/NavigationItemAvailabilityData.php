<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Spatie\LaravelData\Data;

final class NavigationItemAvailabilityData extends Data
{
    public function __construct(
        public string $position,
        public string $status,
    ) {}
}
