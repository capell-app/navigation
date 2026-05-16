<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

final class BuildPageNavigationReferencesAction
{
    use AsObject;

    /**
     * @return Collection<int, Navigation>
     */
    public function handle(Pageable $record): Collection
    {
        if (! $record instanceof Model) {
            return new Collection;
        }

        /** @var class-string<Navigation> $model */
        $model = Navigation::class;
        $siteId = $record->getAttribute('site_id');
        $recordId = (int) $record->getKey();
        $recordMorphClass = $record->getMorphClass();

        return new Collection($model::with(['language', 'site'])
            ->when(
                is_numeric($siteId),
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query->whereNull('site_id')
                        ->orWhere('site_id', (int) $siteId),
                ),
            )
            ->where(fn (Builder $query): Builder => $this->whereItemsMightContainRecord($query, $recordId, $recordMorphClass))
            ->orderBy('site_id')
            ->orderBy('name')
            ->orderBy('language_id')
            ->get()
            ->filter(fn (Navigation $navigation): bool => $this->navigationContainsRecord($navigation, $record))
            ->values()
            ->all());
    }

    private function whereItemsMightContainRecord(Builder $query, int $recordId, string $recordMorphClass): Builder
    {
        $encodedMorphClass = trim(json_encode($recordMorphClass, JSON_THROW_ON_ERROR), '"');

        return $query
            ->where(function (Builder $query) use ($recordId): void {
                foreach ($this->jsonNumberFragments('pageable_id', $recordId) as $fragment) {
                    $query->orWhere('items', 'like', '%' . $fragment . '%');
                }
            })
            ->where(function (Builder $query) use ($encodedMorphClass): void {
                foreach ($this->jsonStringFragments('pageable_type', $encodedMorphClass) as $fragment) {
                    $query->orWhere('items', 'like', '%' . $fragment . '%');
                }
            });
    }

    /**
     * @return array<int, string>
     */
    private function jsonNumberFragments(string $key, int $value): array
    {
        return [
            sprintf('"%s":%d', $key, $value),
            sprintf('"%s": %d', $key, $value),
            sprintf('"%s":"%d"', $key, $value),
            sprintf('"%s": "%d"', $key, $value),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function jsonStringFragments(string $key, string $value): array
    {
        return [
            sprintf('"%s":"%s"', $key, $value),
            sprintf('"%s": "%s"', $key, $value),
        ];
    }

    private function navigationContainsRecord(Navigation $navigation, Model $record): bool
    {
        return $this->itemsContainRecord($navigation->items, (int) $record->getKey(), $record->getMorphClass());
    }

    private function itemsContainRecord(mixed $items, int $recordId, string $recordMorphClass): bool
    {
        if ($items instanceof DataCollection) {
            $items = $items->all();
        }

        if (! is_iterable($items)) {
            return false;
        }

        foreach ($items as $item) {
            if ($this->itemContainsRecord($item, $recordId, $recordMorphClass)) {
                return true;
            }
        }

        return false;
    }

    private function itemContainsRecord(mixed $item, int $recordId, string $recordMorphClass): bool
    {
        if ($item instanceof NavigationItemData) {
            return $this->itemDataMatches($item->data, $recordId, $recordMorphClass)
                || $this->itemsContainRecord($item->children, $recordId, $recordMorphClass);
        }

        if (! is_array($item)) {
            return false;
        }

        $data = is_array($item['data'] ?? null) ? $item['data'] : [];

        return $this->itemDataMatches($data, $recordId, $recordMorphClass)
            || $this->itemsContainRecord($item['children'] ?? [], $recordId, $recordMorphClass);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function itemDataMatches(array $data, int $recordId, string $recordMorphClass): bool
    {
        return (int) ($data['pageable_id'] ?? 0) === $recordId
            && ($data['pageable_type'] ?? null) === $recordMorphClass;
    }
}
