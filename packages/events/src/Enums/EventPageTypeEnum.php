<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

enum EventPageTypeEnum: string
{
    case Calendar = 'events-calendar';
    case Event = 'event';
    case Events = 'events';
}
