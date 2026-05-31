<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

/**
 * @method static void run(Pageable $page, Navigation $navigation, ?string $label = null)
 */
class AddPageToNavigationAction
{
    use AsObject;

    public function handle(Pageable $page, Navigation $navigation, ?string $label = null): void
    {
        DB::transaction(function () use ($label, $navigation, $page): void {
            if ($navigation->getKey() === null) {
                return;
            }

            /** @var Navigation $lockedNavigation */
            $lockedNavigation = $navigation->newQuery()
                ->whereKey($navigation->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedNavigation instanceof Navigation) {
                return;
            }

            if ($this->pageExistsInNavigation($lockedNavigation, $page)) {
                return;
            }

            $items = $this->navigationItemsArray($lockedNavigation);

            $items[(string) Str::uuid()] = [
                'label' => $label,
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $page->site_id,
                    'pageable_id' => (int) $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ];

            $lockedNavigation->update(['items' => $items]);
        });
    }

    private function pageExistsInNavigation(Navigation $navigation, Pageable $page): bool
    {
        return $this->itemsContainPage($this->navigationItemsIterable($navigation), $page);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function navigationItemsArray(Navigation $navigation): array
    {
        if ($navigation->items instanceof DataCollection) {
            return $navigation->items->toArray();
        }

        return is_array($navigation->items) ? $navigation->items : [];
    }

    /**
     * @return iterable<array-key, array<string, mixed>|NavigationItemData>
     */
    private function navigationItemsIterable(Navigation $navigation): iterable
    {
        if ($navigation->items instanceof DataCollection) {
            return $navigation->items->toCollection();
        }

        return is_array($navigation->items) ? $navigation->items : [];
    }

    /**
     * @param  iterable<array-key, array<string, mixed>|NavigationItemData>  $items
     */
    private function itemsContainPage(iterable $items, Pageable $page): bool
    {
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = NavigationItemData::from($item);
            }

            if (! $item instanceof NavigationItemData) {
                continue;
            }

            if ($item->type === NavigationItemType::Page
                && ($item->data['pageable_type'] ?? null) === $page->getMorphClass()
                && (int) ($item->data['pageable_id'] ?? 0) === $page->getKey()) {
                return true;
            }

            if ($this->itemsContainPage($item->children?->toCollection() ?? [], $page)) {
                return true;
            }
        }

        return false;
    }
}
