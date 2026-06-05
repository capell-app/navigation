<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
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
        $recordSiteId = is_numeric($siteId) ? (int) $siteId : null;
        $recordId = (int) $record->getKey();
        $recordMorphClass = $record->getMorphClass();

        $references = new Collection($model::query()
            ->with(['language', 'site'])
            ->whereExists(
                fn (QueryBuilder $query): QueryBuilder => $this->referenceExistsQuery(
                    query: $query,
                    recordMorphClass: $recordMorphClass,
                    recordId: $recordId,
                    recordSiteId: $recordSiteId,
                ),
            )
            ->when(
                $recordSiteId !== null,
                fn (Builder $query): Builder => $this->whereNavigationMatchesSite($query, $recordSiteId),
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

    private function referenceExistsQuery(
        QueryBuilder $query,
        string $recordMorphClass,
        int $recordId,
        ?int $recordSiteId,
    ): QueryBuilder {
        return $query
            ->from('navigation_page_references')
            ->select('navigation_page_references.navigation_id')
            ->whereColumn('navigation_page_references.navigation_id', 'navigations.id')
            ->where('navigation_page_references.pageable_type', $recordMorphClass)
            ->where('navigation_page_references.pageable_id', $recordId)
            ->when(
                $recordSiteId !== null,
                fn (QueryBuilder $query): QueryBuilder => $this->whereReferenceMatchesSite($query, $recordSiteId),
            );
    }

    private function whereNavigationMatchesSite(Builder $query, int $recordSiteId): Builder
    {
        return $query->where(
            fn (Builder $query): Builder => $query->whereNull('site_id')
                ->orWhere('site_id', $recordSiteId),
        );
    }

    private function whereReferenceMatchesSite(QueryBuilder $query, int $recordSiteId): QueryBuilder
    {
        return $query->where(
            fn (QueryBuilder $query): QueryBuilder => $query->whereNull('navigation_page_references.site_id')
                ->orWhere('navigation_page_references.site_id', $recordSiteId),
        );
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
