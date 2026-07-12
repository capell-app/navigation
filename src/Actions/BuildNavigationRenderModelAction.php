<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Enums\NavigationChildrenLoadingEnum;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\Compilers\ComponentTagCompiler;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static NavigationRenderData run(NavigationRenderContextData $context)
 */
class BuildNavigationRenderModelAction
{
    use AsObject;

    private const string REQUEST_CACHE_KEY = 'capell.navigation.render_models';

    public static function flushPageCache(): void
    {
        NavigationItemsLoader::flushPageCache();
        self::flushSharedRenderModelCache();

        if (app()->bound('request')) {
            request()->attributes->remove(self::REQUEST_CACHE_KEY);
        }
    }

    public static function flushSharedRenderModelCache(): void
    {
        if (Cache::supportsTags()) {
            Cache::tags(['navigation'])->flush();
        }
    }

    public function handle(NavigationRenderContextData $context): NavigationRenderData
    {
        $cacheKey = $this->cacheKey($context);
        $request = app()->bound('request') ? request() : null;
        $cache = $this->requestCache($request);

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $renderModel = $this->sharedCacheKey($context) !== null
            ? $this->rememberSharedRenderModel($context)
            : $this->buildRenderModel($context);

        $cache[$cacheKey] = $renderModel;
        $request?->attributes->set(self::REQUEST_CACHE_KEY, $cache);

        return $renderModel;
    }

    /**
     * @return Collection<int, NavigationItemRenderData>|null
     */
    public function childRenderItems(NavigationRenderContextData $context, string $itemKey, string $itemPath): ?Collection
    {
        $renderModel = $this->handle($context);
        $item = $this->findRenderItem($renderModel->items, $itemKey);

        return $item instanceof NavigationItemRenderData ? $item->children : null;
    }

    private function rememberSharedRenderModel(NavigationRenderContextData $context): NavigationRenderData
    {
        $sharedCacheKey = $this->sharedCacheKey($context);

        if ($sharedCacheKey === null) {
            return $this->buildRenderModel($context);
        }

        $repository = Cache::supportsTags()
            ? Cache::tags(['navigation'])
            : Cache::store();

        $cachedPayload = $repository->get($sharedCacheKey);

        if ($cachedPayload !== null) {
            $renderModel = $this->renderModelFromCachePayload($cachedPayload);

            if ($renderModel instanceof NavigationRenderData) {
                return $renderModel;
            }

            $repository->forget($sharedCacheKey);
        }

        $renderModel = $this->buildRenderModel($context);

        $repository->put($sharedCacheKey, $this->renderModelCachePayload($renderModel), now()->addMinutes(5));

        return $renderModel;
    }

    private function buildRenderModel(NavigationRenderContextData $context): NavigationRenderData
    {
        $loader = new NavigationItemsLoader(
            navigation: $context->navigation,
            page: $context->page,
            site: $context->site,
            language: $context->language,
            siteDomain: $context->siteDomain,
        );

        $items = $loader->load();

        return new NavigationRenderData(
            navigationId: $context->navigation->exists ? (int) $context->navigation->getKey() : null,
            navigationKey: $context->navigation->key,
            navigationName: $context->navigation->name,
            listComponent: $this->listComponent($context->navigation),
            items: $this->mapItems($items, $context),
        );
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return Collection<int, NavigationItemRenderData>
     */
    private function mapItems(Collection $items, NavigationRenderContextData $context, string $parentPath = ''): Collection
    {
        return $items
            ->values()
            ->map(fn (NavigationItemData $item, int $itemIndex): NavigationItemRenderData => $this->mapItem(
                item: $item,
                context: $context,
                itemPath: $parentPath === '' ? (string) $itemIndex : $parentPath . '.' . $itemIndex,
            ))
            ->values();
    }

    private function mapItem(NavigationItemData $item, NavigationRenderContextData $context, string $itemPath): NavigationItemRenderData
    {
        $data = $item->data;
        $children = $this->mapItems(collect($item->children?->all() ?? []), $context, $itemPath);
        $icon = $this->icon($data['icon'] ?? null);
        $activeIcon = $this->icon($data['active_icon'] ?? null);

        return new NavigationItemRenderData(
            label: $item->label,
            type: $item->type,
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            active: $item->active === true,
            children: $children,
            data: $this->viewData($data, $icon, $activeIcon),
            target: isset($data['target']) && is_string($data['target']) ? $data['target'] : null,
            rel: $this->rel($item, $data),
            icon: $icon,
            activeIcon: $activeIcon,
            class: isset($data['class']) && is_string($data['class']) ? $data['class'] : null,
            component: isset($data['component']) && is_string($data['component']) ? $data['component'] : null,
            componentItem: isset($data['component_item']) && is_string($data['component_item']) ? $data['component_item'] : null,
            hideLabel: ($data['hide_label'] ?? false) === true,
            key: $item->key,
            lazyFragmentUrl: $this->lazyFragmentUrl($item, $context, $itemPath, $children),
        );
    }

    /**
     * @param  Collection<int, NavigationItemRenderData>  $children
     */
    private function lazyFragmentUrl(NavigationItemData $item, NavigationRenderContextData $context, string $itemPath, Collection $children): ?string
    {
        if ($item->key === null || $children->isEmpty()) {
            return null;
        }

        if (NavigationChildrenLoadingEnum::fromItemData($item->data) !== NavigationChildrenLoadingEnum::Lazy) {
            return null;
        }

        $payload = Crypt::encryptString(json_encode([
            'version' => 1,
            'expires_at' => now()->addMinutes(5)->getTimestamp(),
            'navigation' => $this->integerKey($context->navigation->getKey()),
            'navigation_version' => $context->navigation->updated_at?->getTimestamp(),
            'visible_from' => $context->navigation->visible_from?->getTimestamp(),
            'visible_until' => $context->navigation->visible_until?->getTimestamp(),
            'item' => $item->key,
            'path' => $itemPath,
            'page' => $this->integerKey($context->page->getKey()),
            'page_type' => $context->page->getMorphClass(),
            'site' => $this->integerKey($context->site->getKey()),
            'language' => $this->integerKey($context->language->getKey()),
            'domain' => $this->integerKey($context->siteDomain->getKey()),
            'host' => strtolower($context->siteDomain->domain),
        ], JSON_THROW_ON_ERROR));

        return route('capell-navigation.children', ['payload' => $payload]);
    }

    /**
     * @param  Collection<int, NavigationItemRenderData>  $items
     */
    private function findRenderItem(Collection $items, string $itemKey): ?NavigationItemRenderData
    {
        foreach ($items as $item) {
            if ($item->key === $itemKey) {
                return $item;
            }

            $child = $this->findRenderItem($item->children, $itemKey);

            if ($child instanceof NavigationItemRenderData) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(array $data, ?string $icon, ?string $activeIcon): array
    {
        $viewData = [];

        foreach ([
            'url',
            'target',
            'rel',
            'active_mode',
            'visibility',
            'icon',
            'active_icon',
            'class',
            'component',
            'component_item',
            'hide_label',
            'dropdown_layout',
            'mega_columns',
            'mega_panel_heading',
            'mega_panel_description',
            'mega_panel_url',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $viewData[$key] = $data[$key];
            }
        }

        if ($icon !== null) {
            $viewData['icon'] = $icon;
        } else {
            unset($viewData['icon']);
        }

        if ($activeIcon !== null) {
            $viewData['active_icon'] = $activeIcon;
        } else {
            unset($viewData['active_icon']);
        }

        return $viewData;
    }

    private function icon(mixed $icon): ?string
    {
        if (! is_string($icon) || $icon === '') {
            return null;
        }

        try {
            $this->componentTagCompiler()->componentClass($icon);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $icon;
    }

    private function componentTagCompiler(): ComponentTagCompiler
    {
        $bladeCompiler = app('blade.compiler');

        return new ComponentTagCompiler(
            $bladeCompiler->getClassComponentAliases(),
            $bladeCompiler->getClassComponentNamespaces(),
            $bladeCompiler,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rel(NavigationItemData $item, array $data): ?string
    {
        if (isset($data['rel']) && is_string($data['rel']) && trim($data['rel']) !== '') {
            return trim($data['rel']);
        }

        if (($data['target'] ?? null) !== '_blank') {
            return null;
        }

        $url = $data['url'] ?? null;

        if ($item->type === NavigationItemType::ExternalLink || (is_string($url) && preg_match('/^[a-z][a-z0-9+.-]*:/i', $url) === 1)) {
            return 'noopener noreferrer';
        }

        return null;
    }

    private function listComponent(Navigation $navigation): string
    {
        $component = $navigation->getMeta('component', 'capell::list');

        return is_string($component) && $component !== '' ? $component : 'capell::list';
    }

    /**
     * @return array<string, NavigationRenderData>
     */
    private function requestCache(?Request $request): array
    {
        if (! $request instanceof Request) {
            return [];
        }

        $cache = $request->attributes->get(self::REQUEST_CACHE_KEY, []);

        return is_array($cache) ? $cache : [];
    }

    private function cacheKey(NavigationRenderContextData $context): string
    {
        return implode('|', [
            $context->navigation->exists ? (string) $context->navigation->getKey() : 'new:' . spl_object_id($context->navigation),
            $context->navigation->key,
            (string) $context->navigation->updated_at?->getTimestamp(),
            $context->page->getMorphClass(),
            (string) $context->page->getKey(),
            (string) $context->site->getKey(),
            (string) $context->language->getKey(),
            (string) $context->siteDomain->getKey(),
        ]);
    }

    private function sharedCacheKey(NavigationRenderContextData $context): ?string
    {
        if (! $context->navigation->exists || auth()->check()) {
            return null;
        }

        return NavigationCacheEnum::renderModelKey(implode('|', [
            $this->cacheKey($context),
            (string) $context->page->updated_at?->getTimestamp(),
            (string) $context->site->updated_at?->getTimestamp(),
            (string) $context->language->updated_at?->getTimestamp(),
            (string) $context->siteDomain->updated_at?->getTimestamp(),
        ]));
    }

    private function integerKey(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : 0;
    }

    /**
     * @return array{navigationId:int|null,navigationKey:string,navigationName:string|null,listComponent:string,items:array<int, array<string, mixed>>}
     */
    private function renderModelCachePayload(NavigationRenderData $renderModel): array
    {
        return [
            'navigationId' => $renderModel->navigationId,
            'navigationKey' => $renderModel->navigationKey,
            'navigationName' => $renderModel->navigationName,
            'listComponent' => $renderModel->listComponent,
            'items' => $renderModel->items
                ->map(fn (NavigationItemRenderData $item): array => $this->renderItemCachePayload($item))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function renderItemCachePayload(NavigationItemRenderData $item): array
    {
        return [
            'label' => $item->label,
            'type' => $item->type->value,
            'url' => $item->url,
            'active' => $item->active,
            'children' => $item->children
                ->map(fn (NavigationItemRenderData $child): array => $this->renderItemCachePayload($child))
                ->values()
                ->all(),
            'data' => $item->data,
            'target' => $item->target,
            'rel' => $item->rel,
            'icon' => $item->icon,
            'activeIcon' => $item->activeIcon,
            'class' => $item->class,
            'component' => $item->component,
            'componentItem' => $item->componentItem,
            'hideLabel' => $item->hideLabel,
            'key' => $item->key,
            'lazyFragmentUrl' => $item->lazyFragmentUrl,
        ];
    }

    private function renderModelFromCachePayload(mixed $payload): ?NavigationRenderData
    {
        if (! is_array($payload)) {
            return null;
        }

        $navigationId = $payload['navigationId'] ?? null;
        $navigationKey = $payload['navigationKey'] ?? null;
        $navigationName = $payload['navigationName'] ?? null;
        $listComponent = $payload['listComponent'] ?? null;
        $items = $this->renderItemsFromCachePayload($payload['items'] ?? null);

        if (
            (! is_int($navigationId) && $navigationId !== null)
            || ! is_string($navigationKey)
            || (! is_string($navigationName) && $navigationName !== null)
            || ! is_string($listComponent)
            || ! $items instanceof Collection
        ) {
            return null;
        }

        return new NavigationRenderData(
            navigationId: $navigationId,
            navigationKey: $navigationKey,
            navigationName: $navigationName,
            listComponent: $listComponent,
            items: $items,
        );
    }

    /**
     * @return Collection<int, NavigationItemRenderData>|null
     */
    private function renderItemsFromCachePayload(mixed $payload): ?Collection
    {
        if (! is_array($payload)) {
            return null;
        }

        $items = [];

        foreach ($payload as $itemPayload) {
            $item = $this->renderItemFromCachePayload($itemPayload);

            if (! $item instanceof NavigationItemRenderData) {
                return null;
            }

            $items[] = $item;
        }

        return collect($items);
    }

    private function renderItemFromCachePayload(mixed $payload): ?NavigationItemRenderData
    {
        if (! is_array($payload)) {
            return null;
        }

        $typeValue = $payload['type'] ?? null;
        $active = $payload['active'] ?? null;
        $data = $this->stringKeyedArray($payload['data'] ?? null);
        $children = $this->renderItemsFromCachePayload($payload['children'] ?? null);

        if (! is_string($typeValue) || ! is_bool($active) || ! is_array($data) || ! $children instanceof Collection) {
            return null;
        }

        $type = NavigationItemType::tryFrom($typeValue);

        if (! $type instanceof NavigationItemType) {
            return null;
        }

        return new NavigationItemRenderData(
            label: $this->nullableString($payload, 'label'),
            type: $type,
            url: $this->nullableString($payload, 'url'),
            active: $active,
            children: $children,
            data: $data,
            target: $this->nullableString($payload, 'target'),
            rel: $this->nullableString($payload, 'rel'),
            icon: $this->nullableString($payload, 'icon'),
            activeIcon: $this->nullableString($payload, 'activeIcon'),
            class: $this->nullableString($payload, 'class'),
            component: $this->nullableString($payload, 'component'),
            componentItem: $this->nullableString($payload, 'componentItem'),
            hideLabel: ($payload['hideLabel'] ?? false) === true,
            key: $this->nullableString($payload, 'key'),
            lazyFragmentUrl: $this->nullableString($payload, 'lazyFragmentUrl'),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function stringKeyedArray(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $array = [];

        foreach ($value as $key => $item) {
            if (! is_string($key)) {
                return null;
            }

            $array[$key] = $item;
        }

        return $array;
    }
}
