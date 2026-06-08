<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Data\NavigationItemRenderData;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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

    private function rememberSharedRenderModel(NavigationRenderContextData $context): NavigationRenderData
    {
        $sharedCacheKey = $this->sharedCacheKey($context);

        if ($sharedCacheKey === null) {
            return $this->buildRenderModel($context);
        }

        $repository = Cache::supportsTags()
            ? Cache::tags(['navigation'])
            : Cache::store();

        /** @var NavigationRenderData $renderModel */
        $renderModel = $repository->remember(
            $sharedCacheKey,
            now()->addMinutes(5),
            fn (): NavigationRenderData => $this->buildRenderModel($context),
        );

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
            items: $this->mapItems($items),
        );
    }

    /**
     * @param  Collection<int, NavigationItemData>  $items
     * @return Collection<int, NavigationItemRenderData>
     */
    private function mapItems(Collection $items): Collection
    {
        return $items
            ->map(fn (NavigationItemData $item): NavigationItemRenderData => $this->mapItem($item))
            ->values();
    }

    private function mapItem(NavigationItemData $item): NavigationItemRenderData
    {
        $data = $item->data;

        return new NavigationItemRenderData(
            label: $item->label,
            type: $item->type,
            url: isset($data['url']) && is_string($data['url']) ? $data['url'] : null,
            active: $item->active === true,
            children: $this->mapItems(collect($item->children?->all() ?? [])),
            data: $this->viewData($data),
            target: isset($data['target']) && is_string($data['target']) ? $data['target'] : null,
            rel: $this->rel($item, $data),
            icon: isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null,
            activeIcon: isset($data['active_icon']) && is_string($data['active_icon']) ? $data['active_icon'] : null,
            class: isset($data['class']) && is_string($data['class']) ? $data['class'] : null,
            component: isset($data['component']) && is_string($data['component']) ? $data['component'] : null,
            componentItem: isset($data['component_item']) && is_string($data['component_item']) ? $data['component_item'] : null,
            hideLabel: ($data['hide_label'] ?? false) === true,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function viewData(array $data): array
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

        return $viewData;
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
}
