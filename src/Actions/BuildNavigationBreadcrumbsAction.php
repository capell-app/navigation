<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderData;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Collection<int, NavigationItemRenderData> run(NavigationRenderData $navigation)
 */
final class BuildNavigationBreadcrumbsAction
{
    use AsObject;

    /**
     * @return Collection<int, NavigationItemRenderData>
     */
    public function handle(NavigationRenderData $navigation): Collection
    {
        return $this->activeTrail($navigation->items);
    }

    /**
     * @param  Collection<int, NavigationItemRenderData>  $items
     * @return Collection<int, NavigationItemRenderData>
     */
    private function activeTrail(Collection $items): Collection
    {
        foreach ($items as $item) {
            if (! $item->active) {
                continue;
            }

            $children = $this->activeTrail($item->children);

            return collect([$item])->merge($children)->values();
        }

        return collect();
    }
}
