<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

use Capell\Events\Filament\Resources\Events\EventResource;

enum ResourceEnum: string
{
    case Event = EventResource::class;
}
