<?php

declare(strict_types=1);

namespace Capell\Navigation\Manifest;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class NavigationHealthContribution implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }
}
