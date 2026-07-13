<?php

declare(strict_types=1);

namespace Capell\Navigation\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;

final class NavigationFrontendRuntimeContribution implements ExtensionContribution
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
