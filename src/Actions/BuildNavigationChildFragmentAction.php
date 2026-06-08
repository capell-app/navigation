<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static string|null run(string $payload)
 */
class BuildNavigationChildFragmentAction
{
    use AsObject;

    public function handle(string $payload): ?string
    {
        $data = $this->decodePayload($payload);

        if ($data === null) {
            return null;
        }

        $context = $this->context($data);

        if (! $context instanceof NavigationRenderContextData) {
            return null;
        }

        $cacheKey = NavigationCacheEnum::lazyFragmentKey(implode('|', [
            $this->stringValue($context->navigation->getKey()),
            $context->navigation->key,
            $this->stringValue($context->site->getKey()),
            $this->stringValue($context->language->getKey()),
            $data['item'],
            $data['path'],
            (string) $context->navigation->updated_at?->getTimestamp(),
            $context->page->getMorphClass(),
            $this->stringValue($context->page->getKey()),
        ]));

        $repository = Cache::supportsTags()
            ? Cache::tags(['navigation'])
            : Cache::store();

        /** @var string|null $html */
        $html = $repository->remember(
            $cacheKey,
            now()->addMinutes(5),
            fn (): ?string => $this->renderFragment($context, $data['item'], $data['path']),
        );

        return $html;
    }

    /**
     * @param  array{navigation:int, item:string, path:string, page:int, page_type:string, site:int, language:int, domain:int}  $data
     */
    private function context(array $data): ?NavigationRenderContextData
    {
        $navigation = Navigation::query()->find($data['navigation']);
        $site = Site::query()->find($data['site']);
        $language = Language::query()->find($data['language']);
        $siteDomain = SiteDomain::query()->find($data['domain']);
        $pageClass = Relation::getMorphedModel($data['page_type']) ?? $data['page_type'];

        if (! is_string($pageClass) || ! is_subclass_of($pageClass, Model::class) || ! is_subclass_of($pageClass, Pageable::class)) {
            return null;
        }

        /** @var class-string<Model&Pageable<Model>> $pageClass */
        $page = $pageClass::query()->with(['translation', 'pageUrl'])->find($data['page']);

        if (! $navigation instanceof Navigation || ! $site instanceof Site || ! $language instanceof Language || ! $siteDomain instanceof SiteDomain || ! $page instanceof Pageable) {
            return null;
        }

        return new NavigationRenderContextData(
            navigation: $navigation,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $siteDomain,
        );
    }

    /**
     * @return array{navigation:int, item:string, path:string, page:int, page_type:string, site:int, language:int, domain:int}|null
     */
    private function decodePayload(string $payload): ?array
    {
        try {
            $data = json_decode(Crypt::decryptString($payload), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($data)) {
            return null;
        }

        foreach (['navigation', 'page', 'site', 'language', 'domain'] as $key) {
            if (! is_numeric($data[$key] ?? null)) {
                return null;
            }
        }

        foreach (['item', 'path', 'page_type'] as $key) {
            if (! is_string($data[$key] ?? null) || $data[$key] === '') {
                return null;
            }
        }

        return [
            'navigation' => (int) $data['navigation'],
            'item' => $data['item'],
            'path' => $data['path'],
            'page' => (int) $data['page'],
            'page_type' => $data['page_type'],
            'site' => (int) $data['site'],
            'language' => (int) $data['language'],
            'domain' => (int) $data['domain'],
        ];
    }

    private function renderFragment(NavigationRenderContextData $context, string $itemKey, string $itemPath): ?string
    {
        $items = (new BuildNavigationRenderModelAction)->childRenderItems($context, $itemKey, $itemPath);

        if ($items === null) {
            return null;
        }

        return View::make('capell-navigation::components.menu-items', [
            'items' => $items,
            'includeNavigationLazyLoader' => false,
        ])->render();
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_int($value) || is_float($value)
            ? (string) $value
            : '';
    }
}
