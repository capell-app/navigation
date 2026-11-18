<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

/**
 * The value that makes a live menu's key unique within its site and language, with "all sites" and "all languages"
 * counted as a scope of their own rather than as NULLs that a database unique index would let repeat.
 */
final class NavigationKeyScope
{
    public static function for(string $key, ?int $siteId, ?int $languageId): string
    {
        return hash('sha256', implode('|', [
            $key,
            $siteId === null ? 'all' : (string) $siteId,
            $languageId === null ? 'all' : (string) $languageId,
        ]));
    }
}
