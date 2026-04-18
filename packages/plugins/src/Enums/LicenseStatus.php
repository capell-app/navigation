<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case PastDue = 'past_due';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
