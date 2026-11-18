<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

use BladeUI\Icons\Components\Svg;

/**
 * Stored icon names are rendered as Blade components, so only names that
 * Blade Icons registered as an icon alias are accepted. Other registered
 * components (for example the dynamic-component alias) are rejected.
 */
final class SafeIcon
{
    public static function resolve(mixed $icon): ?string
    {
        if (! is_string($icon) || $icon === '' || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)+$/', $icon) !== 1) {
            return null;
        }

        $aliases = app('blade.compiler')->getClassComponentAliases();

        return ($aliases[$icon] ?? null) === Svg::class ? $icon : null;
    }
}
