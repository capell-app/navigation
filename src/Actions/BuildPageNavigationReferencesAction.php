<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

final class BuildPageNavigationReferencesAction
{
    use AsObject;

    private const string REQUEST_CACHE_KEY = 'capell.navigation.page_references';

    public static function flushRequestCache(): void
    {
        if (app()->bound('request')) {
            request()->attributes->remove(self::REQUEST_CACHE_KEY);
        }
    }

    /**
     * @return Collection<int, Navigation>
     */
    public function handle(Pageable $record): Collection
    {
        if (! $record instanceof Model) {
            return new Collection;
        }

        $cacheKey = $this->cacheKey($record);
        $request = app()->bound('request') ? request() : null;
        $cache = $this->requestCache($request);

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        if (! Schema::hasTable('navigation_page_references')) {
            return new Collection;
        }

        /** @var class-string<Navigation> $model */
        $model = Navigation::class;
        $siteId = $record->getAttribute('site_id');
        $recordId = (int) $record->getKey();
        $recordMorphClass = $record->getMorphClass();
        $navigationIds = DB::table('navigation_page_references')
            ->where('pageable_type', $recordMorphClass)
            ->where('pageable_id', $recordId)
            ->when(
                is_numeric($siteId),
                fn (\Illuminate\Database\Query\Builder $query): \Illuminate\Database\Query\Builder => $query->where(
                    fn (\Illuminate\Database\Query\Builder $query): \Illuminate\Database\Query\Builder => $query->whereNull('site_id')
                        ->orWhere('site_id', (int) $siteId),
                ),
            )
            ->orderBy('navigation_id')
            ->pluck('navigation_id')
            ->map(static fn (mixed $navigationId): int => (int) $navigationId)
            ->unique()
            ->values()
            ->all();

        if ($navigationIds === []) {
            $references = new Collection;
            $cache[$cacheKey] = $references;
            $request?->attributes->set(self::REQUEST_CACHE_KEY, $cache);

            return $references;
        }

        $references = new Collection($model::with(['language', 'site'])
            ->whereKey($navigationIds)
            ->when(
                is_numeric($siteId),
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $query): Builder => $query->whereNull('site_id')
                        ->orWhere('site_id', (int) $siteId),
                ),
            )
            ->orderBy('site_id')
            ->orderBy('name')
            ->orderBy('language_id')
            ->get()
            ->filter(fn (Navigation $navigation): bool => $this->navigationContainsRecord($navigation, $record))
            ->values()
            ->all());

        $cache[$cacheKey] = $references;
        $request?->attributes->set(self::REQUEST_CACHE_KEY, $cache);

        return $references;
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
            if ($this->itemDataMatches($item->data, $recordId, $recordMorphClass)) {
                return true;
            }

            return $this->itemsContainRecord($item->children, $recordId, $recordMorphClass);
        }

        if (! is_array($item)) {
            return false;
        }

        $data = is_array($item['data'] ?? null) ? $item['data'] : [];
        if ($this->itemDataMatches($data, $recordId, $recordMorphClass)) {
            return true;
        }

        return $this->itemsContainRecord($item['children'] ?? [], $recordId, $recordMorphClass);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function itemDataMatches(array $data, int $recordId, string $recordMorphClass): bool
    {
        return (int) ($data['pageable_id'] ?? 0) === $recordId
            && ($data['pageable_type'] ?? null) === $recordMorphClass;
    }

    /**
     * @return array<string, Collection<int, Navigation>>
     */
    private function requestCache(?Request $request): array
    {
        if (! $request instanceof Request) {
            return [];
        }

        $cache = $request->attributes->get(self::REQUEST_CACHE_KEY, []);

        return is_array($cache) ? $cache : [];
    }

    private function cacheKey(Model $record): string
    {
        return implode('|', [
            $record->getMorphClass(),
            (string) $record->getKey(),
            (string) $record->getAttribute('site_id'),
        ]);
    }
}
