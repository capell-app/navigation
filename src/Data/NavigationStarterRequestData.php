<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Spatie\LaravelData\Data;

/**
 * The scope a navigation starter is generated for.
 *
 * A starter is always site-scoped: a global navigation would otherwise pull
 * top-level pages from every site the actor can reach into one menu.
 */
class NavigationStarterRequestData extends Data
{
    public function __construct(
        public ?int $siteId = null,
        public ?int $languageId = null,
        public int $limit = 20,
    ) {}

    public static function fromState(mixed $siteId, mixed $languageId, int $limit = 20): self
    {
        return new self(
            siteId: self::intOrNull($siteId),
            languageId: self::intOrNull($languageId),
            limit: $limit,
        );
    }

    private static function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($integer) ? $integer : null;
    }
}
