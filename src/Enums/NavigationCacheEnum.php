<?php

declare(strict_types=1);

namespace Capell\Navigation\Enums;

/**
 * Cache keys specific to navigation functionality.
 */
enum NavigationCacheEnum: string
{
    case NavigationNames = 'navigation-names';
    case RenderModels = 'navigation-render-models';
    case LazyFragments = 'navigation-lazy-fragments';

    /**
     * Cache key for navigation name set filtered by site and languages.
     *
     * @param  array<array-key, mixed>  $languageIds
     */
    public static function navigationNamesKey(int|string|null $siteId, array $languageIds): string
    {
        $languageIdsHash = hash('sha256', json_encode($languageIds, JSON_THROW_ON_ERROR));

        return self::NavigationNames->value . '-' . ($siteId ?? 'null') . '-' . $languageIdsHash;
    }

    public static function renderModelKey(string $context): string
    {
        return self::RenderModels->value . '-' . hash('sha256', $context);
    }

    public static function lazyFragmentKey(string $context): string
    {
        return self::LazyFragments->value . '-' . hash('sha256', $context);
    }
}
