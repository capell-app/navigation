<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemData;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

/**
 * @method static DataCollection<int|string, NavigationItemData> run(DataCollection<int|string, NavigationItemData>|array<array-key, mixed>|null $items)
 */
class EnsureNavigationItemKeysAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  DataCollection<int|string, NavigationItemData>|array<array-key, mixed>|null  $items
     * @return DataCollection<int|string, NavigationItemData>
     */
    public function handle(DataCollection|array|null $items): DataCollection
    {
        return NavigationItemData::collect($this->normalizeItems($items), DataCollection::class);
    }

    /**
     * @param  DataCollection<int|string, NavigationItemData>|array<array-key, mixed>|null  $items
     * @return array<int|string, NavigationItemData>
     */
    private function normalizeItems(DataCollection|array|null $items): array
    {
        $navigationItems = $items instanceof DataCollection
            ? $items->all()
            : (is_array($items) ? $items : []);

        $normalized = [];

        foreach ($navigationItems as $collectionKey => $item) {
            $navigationItem = $item instanceof NavigationItemData
                ? $item
                : NavigationItemData::from($item);

            $preservedKey = is_string($collectionKey) && $collectionKey !== '' ? $collectionKey : null;

            $navigationItem->key = $this->validKey($navigationItem->key)
                ?? $preservedKey
                ?? (string) Str::uuid();
            $navigationItem->children = $this->handle($navigationItem->children);

            if (is_string($collectionKey)) {
                $normalized[$collectionKey] = $navigationItem;

                continue;
            }

            $normalized[] = $navigationItem;
        }

        return $normalized;
    }

    private function validKey(?string $key): ?string
    {
        if ($key === null || trim($key) === '') {
            return null;
        }

        return $key;
    }
}
