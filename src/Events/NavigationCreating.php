<?php

declare(strict_types=1);

namespace Capell\Navigation\Events;

use Capell\Navigation\Models\Navigation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

/**
 * @method static void dispatch(Navigation $navigation, Collection<array-key, mixed> $items)
 */
class NavigationCreating
{
    use Dispatchable;

    /**
     * @param  Collection<array-key, mixed>  $items
     */
    public function __construct(
        public Navigation $navigation,
        public Collection $items,
    ) {}
}
