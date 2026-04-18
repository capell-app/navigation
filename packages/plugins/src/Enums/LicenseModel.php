<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum LicenseModel: string
{
    case Free = 'free';
    case PaidOnce = 'paid_once';
    case PaidSubscription = 'paid_subscription';
}
