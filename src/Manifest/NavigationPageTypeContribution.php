<?php

declare(strict_types=1);

namespace Capell\Navigation\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionPageType;

final class NavigationPageTypeContribution implements ExtensionContribution, RegistersExtensionPageType
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }
}
