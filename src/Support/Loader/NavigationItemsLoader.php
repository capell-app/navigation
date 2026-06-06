<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Loader;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Support\Loader\PageLoader;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemActiveMode;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationItemVisibility;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\DataCollection;

class NavigationItemsLoader
{
    /** @var array<string, array<string, Model&Pageable<Model>>> */
    protected static array $pagesByMorphKeyCache = [];

    /**
     * @param  Model&Pageable<Model>  $page
     */
    public function __construct(
        public Navigation $navigation,
        public Pageable $page,
        public Site $site,
        public Language $language,
        public SiteDomain $siteDomain,
    ) {}

    public static function flushPageCache(): void
    {
        self::$pagesByMorphKeyCache = [];
    }

    /**
     * @return Collection<int, NavigationItemData>
     */
    public function load(): Collection
    {
        $items = $this->fetchMenuItems();

        if ($items->isNotEmpty()) {
            $this->activeMenuItems($items);
        }

        return $items;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     */
    public function activeMenuItems(Collection $items): bool
    {
        $hasActive = false;

        $currentUrl = $this->page->pageUrl->full_url ?? '';

        foreach ($items as $item) {
            $active = false;

            switch ($item->type) {
                case NavigationItemType::Link:
                case NavigationItemType::ExternalLink:
                    if (! isset($item->data['url'])) {
                        continue 2;
                    }

                    if ($item->data['url'] === '') {
                        continue 2;
                    }

                    if ($item->data['url'][0] !== '/') {
                        continue 2;
                    }

                    $domainUrl = $this->siteDomain->url;

                    if (! is_string($domainUrl)) {
                        continue 2;
                    }

                    $url = $domainUrl . $item->data['url'];

                    $url = $url !== '/' ? ltrim($url, '/') : $url;

                    $active = $this->urlMatches($currentUrl, $url, $this->activeMode($item));
                    break;

                case NavigationItemType::Page:
                    $pageableReference = $this->extractPageableReference([
                        'data' => $item->data,
                        'type' => $item->type,
                    ]);

                    $active = $pageableReference !== null
                        && $pageableReference['pageable_id'] === (int) $this->page->getKey()
                        && $pageableReference['pageable_type'] === $this->page->getMorphClass();

                    if (! $active && isset($item->data['url']) && is_string($item->data['url'])) {
                        $active = $this->urlMatches($currentUrl, $item->data['url'], $this->activeMode($item));
                    }
                    break;
            }

            if ($active) {
                $hasActive = true;
                $item->active = true;
            }

            $children = collect($item->children?->all() ?? []);

            if ($children->isNotEmpty()) {
                $activeChild = $this->activeMenuItems($children);

                if ($activeChild) {
                    $hasActive = true;
                    $item->active = true;
                }
            }
        }

        return $hasActive;
    }

    /**
     * @return Collection<int, NavigationItemData>
     */
    public function fetchMenuItems(): Collection
    {
        $items = $this->visibleMenuItems(collect($this->navigationItems()));

        if ($items->isEmpty()) {
            return collect();
        }

        // Cast to Illuminate\Support\Collection explicitly
        $pageableIdsByType = $this->extractMenuItemsPagesByType(new Collection($items->all()));

        $pagesByMorphKey = $this->getPagesByMorphKey($pageableIdsByType);

        // Cast to Illuminate\Support\Collection explicitly
        $result = $this->menuItemPageSetup($this->navigation, new Collection($items->all()), $pagesByMorphKey);

        /** @var DataCollection<int, NavigationItemData> $navigationItems */
        $navigationItems = NavigationItemData::collect(array_values($result->all()), DataCollection::class);

        $this->navigation->items = $navigationItems;

        return collect($navigationItems->all());
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @param  array<string, Model&Pageable<Model>>  $pagesByMorphKey
     * @return DataCollection<int|string, NavigationItemData>
     */
    protected function menuItemPageSetup(Navigation $navigation, Collection $items, array $pagesByMorphKey): DataCollection
    {
        $result = [];

        $items->each(function (NavigationItemData $item) use (&$result, $navigation, $pagesByMorphKey): void {
            $menuItem = [
                'label' => $item->label,
                'type' => $item->type,
                'data' => $item->data,
                'children' => $item->children,
                'is_visible' => $item->is_visible,
            ];

            $children = collect($item->children?->all() ?? []);

            // Cast to Illuminate\Support\Collection explicitly
            if ($children->isNotEmpty()) {
                $menuItem['children'] = $this->menuItemPageSetup($navigation, new Collection($children->all()), $pagesByMorphKey);
            } elseif (isset($item->data['auto_children']) && $item->data['auto_children'] === true) {
                $pageableReference = $this->extractPageableReference([
                    'data' => $item->data,
                    'type' => $item->type,
                ]);

                if ($pageableReference === null || $pageableReference['pageable_type'] !== $this->page->getMorphClass()) {
                    $result[] = new NavigationItemData(...$menuItem);

                    return;
                }

                $children = PageLoader::getPages(
                    language: $this->language,
                    site: $this->site,
                    ordering: PageOrderEnum::Default,
                    optionalLanguage: true,
                    withChildren: true,
                    cacheKeyPrepend: 'parent-' . $pageableReference['pageable_id'],
                    modifyQuery: function (BuilderContract $query) use ($pageableReference): void {
                        $query->where(
                            'parent_id',
                            $pageableReference['pageable_id'],
                        );
                    },
                );

                if ($children->isNotEmpty()) {
                    $menuItem['children'] = NavigationItemData::fromPages($children);
                }
            }

            if ($item->type === NavigationItemType::Page) {
                $pageableReference = $this->extractPageableReference([
                    'data' => $item->data,
                    'type' => $item->type,
                ]);

                if ($pageableReference === null) {
                    return;
                }

                $lookupKey = $this->buildMorphLookupKey(
                    $pageableReference['pageable_type'],
                    $pageableReference['pageable_id'],
                );

                /** @var ?Pageable $page */
                $page = $pagesByMorphKey[$lookupKey] ?? null;

                if (! $page instanceof Pageable) {
                    return;
                }

                if (blank($item->label)) {
                    $menuItem['label'] = $page->translation->label ?? $page->name;
                }

                $menuItem['data']['url'] = $page->pageUrl->full_url ?? '#';
            }

            $result[] = new NavigationItemData(...$menuItem);
        });

        return NavigationItemData::collect($result, DataCollection::class);
    }

    protected function urlMatches(string $currentUrl, string $menuUrl, NavigationItemActiveMode $activeMode = NavigationItemActiveMode::Exact): bool
    {
        // Normalize both URLs for comparison
        $normalizedCurrent = trim($currentUrl, '/');
        $normalizedMenu = trim($menuUrl, '/');

        if ($normalizedCurrent === $normalizedMenu) {
            return true;
        }

        return $activeMode === NavigationItemActiveMode::StartsWith
            && $normalizedMenu !== ''
            && str_starts_with($normalizedCurrent . '/', $normalizedMenu . '/');
    }

    private function activeMode(NavigationItemData $item): NavigationItemActiveMode
    {
        $mode = $item->data['active_mode'] ?? NavigationItemActiveMode::Exact->value;

        return is_string($mode)
            ? NavigationItemActiveMode::tryFrom($mode) ?? NavigationItemActiveMode::Exact
            : NavigationItemActiveMode::Exact;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return Collection<int, NavigationItemData>
     */
    protected function visibleMenuItems(Collection $items): Collection
    {
        return $items
            ->filter(fn (NavigationItemData $item): bool => $item->is_visible && $this->passesVisibilityCondition($item))
            ->map(function (NavigationItemData $item): NavigationItemData {
                $children = $this->visibleMenuItems(collect($item->children?->all() ?? []));

                $item->children = NavigationItemData::collect($children->all(), DataCollection::class);

                return $item;
            })
            ->values();
    }

    private function passesVisibilityCondition(NavigationItemData $item): bool
    {
        $visibility = $this->visibility($item);

        return match ($visibility) {
            NavigationItemVisibility::Everyone => true,
            NavigationItemVisibility::Guests => Auth::guest(),
            NavigationItemVisibility::Authenticated => Auth::check(),
            NavigationItemVisibility::Ability => $this->passesAbilityCondition($item),
            NavigationItemVisibility::Role => $this->passesRoleCondition($item),
        };
    }

    private function visibility(NavigationItemData $item): NavigationItemVisibility
    {
        $visibility = $item->data['visibility'] ?? NavigationItemVisibility::Everyone->value;

        return is_string($visibility)
            ? NavigationItemVisibility::tryFrom($visibility) ?? NavigationItemVisibility::Everyone
            : NavigationItemVisibility::Everyone;
    }

    private function passesAbilityCondition(NavigationItemData $item): bool
    {
        $ability = $item->data['ability'] ?? null;

        return is_string($ability)
            && $ability !== ''
            && Gate::allows($ability);
    }

    private function passesRoleCondition(NavigationItemData $item): bool
    {
        $role = $item->data['role'] ?? null;
        $user = Auth::user();

        return is_string($role)
            && $role !== ''
            && is_object($user)
            && method_exists($user, 'hasRole')
            && $user->hasRole($role) === true;
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return array<string, array<int, int>>
     */
    protected function extractMenuItemsPagesByType(Collection $items): array
    {
        $pageableIdsByType = [];

        foreach ($items as $item) {
            $pageableReference = $this->extractPageableReference([
                'data' => $item->data,
                'type' => $item->type,
            ]);

            if ($item->type === NavigationItemType::Page && $pageableReference !== null) {
                $pageableType = $pageableReference['pageable_type'];
                $pageableId = $pageableReference['pageable_id'];

                if (! isset($pageableIdsByType[$pageableType])) {
                    $pageableIdsByType[$pageableType] = [];
                }

                if (! in_array($pageableId, $pageableIdsByType[$pageableType], true)) {
                    $pageableIdsByType[$pageableType][] = $pageableId;
                }
            }

            $children = collect($item->children?->all() ?? []);

            if ($children->isNotEmpty()) {
                $nestedPageableIdsByType = $this->extractMenuItemsPagesByType($children);

                foreach ($nestedPageableIdsByType as $pageableType => $nestedPageableIds) {
                    if (! isset($pageableIdsByType[$pageableType])) {
                        $pageableIdsByType[$pageableType] = [];
                    }

                    foreach ($nestedPageableIds as $nestedPageableId) {
                        if (! in_array($nestedPageableId, $pageableIdsByType[$pageableType], true)) {
                            $pageableIdsByType[$pageableType][] = $nestedPageableId;
                        }
                    }
                }
            }
        }

        return $pageableIdsByType;
    }

    /**
     * @param  array<string, array<int, int>>  $pageableIdsByType
     * @return array<string, Model&Pageable<Model>>
     */
    protected function getPagesByMorphKey(array $pageableIdsByType): array
    {
        $currentPageType = $this->page->getMorphClass();
        $currentPageId = (int) $this->page->getKey();
        $currentPageLookupKey = $this->buildMorphLookupKey($currentPageType, $currentPageId);
        $currentPage = $this->page;

        if (! $currentPage instanceof Model) {
            return [];
        }

        $pagesByMorphKey = [
            $currentPageLookupKey => $currentPage,
        ];

        foreach ($pageableIdsByType as $pageableType => $pageableIds) {
            if ($pageableIds === []) {
                continue;
            }

            if ($pageableType === $currentPageType) {
                $pageableIds = array_values(array_filter(
                    $pageableIds,
                    static fn (int $pageableId): bool => $pageableId !== $currentPageId,
                ));
            }

            if ($pageableIds === []) {
                continue;
            }

            $cacheKey = implode(':', [
                $pageableType,
                $this->language->getKey(),
                $this->siteDomain->getKey(),
                implode(',', $pageableIds),
            ]);

            if (isset(self::$pagesByMorphKeyCache[$cacheKey])) {
                $pagesByMorphKey = [
                    ...$pagesByMorphKey,
                    ...self::$pagesByMorphKeyCache[$cacheKey],
                ];

                continue;
            }

            $modelClass = Relation::getMorphedModel($pageableType) ?? $pageableType;
            if (! is_string($modelClass)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Pageable::class)) {
                continue;
            }

            /** @var class-string<Model&Pageable<Model>> $modelClass */
            $model = new $modelClass;

            $query = $modelClass::query()
                ->with(['translation', 'pageUrl'])
                ->whereIn($model->getKeyName(), $pageableIds)
                ->orderBy($model->getKeyName());

            $query->where('site_id', $this->site->getKey());

            $pages = $query->get();

            /** @var array<string, Model&Pageable<Model>> $cachedPages */
            $cachedPages = [];

            foreach ($pages as $page) {
                if (! $page instanceof Pageable) {
                    continue;
                }

                if (
                    $page->pageUrl !== null
                    && $page->pageUrl->site_id === $this->siteDomain->site_id
                    && $page->pageUrl->language_id === $this->siteDomain->language_id
                ) {
                    $page->pageUrl->setRelation('siteDomain', $this->siteDomain);
                } elseif ($page->pageUrl !== null) {
                    $page->pageUrl->setRelation(
                        'siteDomain',
                        SiteDomain::query()
                            ->where('site_id', $page->pageUrl->site_id)
                            ->where('language_id', $page->pageUrl->language_id)
                            ->first(),
                    );
                }

                $lookupKey = $this->buildMorphLookupKey($pageableType, (int) $page->getKey());

                $pagesByMorphKey[$lookupKey] = $page;
                $cachedPages[$lookupKey] = $page;
            }

            self::$pagesByMorphKeyCache[$cacheKey] = $cachedPages;
        }

        return $pagesByMorphKey;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{pageable_id:int, pageable_type:string}|null
     */
    protected function extractPageableReference(array $item): ?array
    {
        $pageableId = $item['data']['pageable_id'] ?? null;
        $pageableType = $item['data']['pageable_type'] ?? null;

        if (! is_numeric($pageableId) || ! is_string($pageableType) || $pageableType === '') {
            return null;
        }

        return [
            'pageable_id' => (int) $pageableId,
            'pageable_type' => $pageableType,
        ];
    }

    protected function buildMorphLookupKey(string $pageableType, int $pageableId): string
    {
        return $pageableType . ':' . $pageableId;
    }

    /**
     * @return list<NavigationItemData>
     */
    private function navigationItems(): array
    {
        $items = $this->navigation->items;

        if ($items instanceof DataCollection) {
            /** @var list<NavigationItemData> $navigationItems */
            $navigationItems = array_values($items->all());

            return $navigationItems;
        }

        if (is_array($items)) {
            $dataCollection = NavigationItemData::collect($items, DataCollection::class);

            /** @var list<NavigationItemData> $navigationItems */
            $navigationItems = array_values($dataCollection->all());

            return $navigationItems;
        }

        return [];
    }
}
