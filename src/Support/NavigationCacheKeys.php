<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

use Capell\Navigation\Enums\NavigationCacheEnum;

final class NavigationCacheKeys
{
    public static function lazyFragmentKey(string $context): string
    {
        return NavigationCacheEnum::LazyFragments->value . '-' . hash('sha256', $context);
    }
}
