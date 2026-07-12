<?php

declare(strict_types=1);

namespace Capell\Navigation\Manifest;

use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionRenderHook;

final class NavigationRenderHookContribution implements ExtensionContribution, RegistersExtensionRenderHook
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^0.0';
    }
}
