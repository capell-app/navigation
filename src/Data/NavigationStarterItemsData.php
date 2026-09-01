<?php

declare(strict_types=1);

namespace Capell\Navigation\Data;

use Spatie\LaravelData\Data;

/**
 * Ordinary, editable navigation items produced by a starter.
 *
 * Items are keyed by UUID so the result drops straight into the adjacency-list
 * form state and behaves exactly like hand-added items afterwards.
 */
class NavigationStarterItemsData extends Data
{
    /**
     * @param  array<string, array<string, mixed>>  $items
     */
    public function __construct(
        public array $items = [],
        public int $pageCount = 0,
    ) {}

    public function isEmpty(): bool
    {
        return $this->items === [];
    }
}
