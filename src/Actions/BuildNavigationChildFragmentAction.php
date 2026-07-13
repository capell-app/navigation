<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationCacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;
use UnexpectedValueException;

/**
 * @method static string|null run(string $payload, string $requestHost)
 */
class BuildNavigationChildFragmentAction
{
    use AsObject;

    public function handle(string $payload, string $requestHost): ?string
    {
        $data = $this->decodePayload($payload);

        if ($data === null) {
            return null;
        }

        if (! hash_equals($data['host'], strtolower($requestHost))) {
            return null;
        }

        $context = $this->context($data);

        if (! $context instanceof NavigationRenderContextData) {
            return null;
        }

        $cacheKey = NavigationCacheKeys::lazyFragmentKey(implode('|', [
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
     * @param  array{navigation:int, navigation_version:int, visible_from:int|null, visible_until:int|null, item:string, path:string, page:int, page_type:string, site:int, language:int, domain:int, host:string}  $data
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

        if ($navigation->updated_at?->getTimestamp() !== $data['navigation_version']
            || $navigation->visible_from?->getTimestamp() !== $data['visible_from']
            || $navigation->visible_until?->getTimestamp() !== $data['visible_until']
            || ! $navigation->newQuery()->whereKey($navigation->getKey())->publishedDate()->exists()
            || $siteDomain->site_id !== $this->integerKey($site->getKey())
            || $siteDomain->language_id !== $this->integerKey($language->getKey())
            || ! is_string($siteDomain->domain)
            || strtolower($siteDomain->domain) !== $data['host']
            || $page->site_id !== $this->integerKey($site->getKey())
            || ($navigation->site_id !== null && $navigation->site_id !== $this->integerKey($site->getKey()))
            || ($navigation->language_id !== null && $navigation->language_id !== $this->integerKey($language->getKey()))) {
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

    private function integerKey(mixed $key): int
    {
        if (! is_int($key)) {
            throw new UnexpectedValueException('Expected an integer model key.');
        }

        return $key;
    }

    /**
     * @return array{navigation:int, navigation_version:int, visible_from:int|null, visible_until:int|null, item:string, path:string, page:int, page_type:string, site:int, language:int, domain:int, host:string}|null
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

        if (($data['version'] ?? null) !== 1
            || ! is_int($data['expires_at'] ?? null)
            || $data['expires_at'] < now()->getTimestamp()) {
            return null;
        }

        foreach (['navigation', 'navigation_version', 'page', 'site', 'language', 'domain'] as $key) {
            if (! is_numeric($data[$key] ?? null)) {
                return null;
            }
        }

        foreach (['item', 'path', 'page_type', 'host'] as $key) {
            if (! is_string($data[$key] ?? null) || $data[$key] === '') {
                return null;
            }
        }

        return [
            'navigation' => (int) $data['navigation'],
            'navigation_version' => (int) $data['navigation_version'],
            'visible_from' => is_numeric($data['visible_from'] ?? null) ? (int) $data['visible_from'] : null,
            'visible_until' => is_numeric($data['visible_until'] ?? null) ? (int) $data['visible_until'] : null,
            'item' => $data['item'],
            'path' => $data['path'],
            'page' => (int) $data['page'],
            'page_type' => $data['page_type'],
            'site' => (int) $data['site'],
            'language' => (int) $data['language'],
            'domain' => (int) $data['domain'],
            'host' => strtolower($data['host']),
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
