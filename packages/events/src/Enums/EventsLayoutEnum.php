<?php

declare(strict_types=1);

namespace Capell\Events\Enums;

enum EventsLayoutEnum: string
{
    case Event = 'event';
    case Events = 'events';
    case Calendar = 'events-calendar';
}
