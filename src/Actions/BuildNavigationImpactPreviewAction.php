<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Admin\Support\PageUrlPresenter;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Actions\ResolvePublicPageableMorphTypesAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Data\EditorImpact\EditorImpactPageData;
use Capell\Core\Data\EditorImpact\EditorImpactPreviewData;
use Capell\Core\Data\EditorImpact\EditorImpactUrlData;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Translation;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\SafeUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Build a read-only preview of the public page scope for the current
 * navigation form state. A global scope is shown against every public page
 * visible to the current actor; a site or language scope narrows that scope.
 *
 * @method static EditorImpactPreviewData|null run(Navigation $navigation, ?int $siteId, ?int $languageId)
 */
final class BuildNavigationImpactPreviewAction
{
    use AsFake;
    use AsObject;

    public function handle(Navigation $navigation, ?int $siteId, ?int $languageId): ?EditorImpactPreviewData
    {
        if (! $this->canUpdateNavigation($navigation)) {
            return null;
        }

        $pages = collect();
        $siteIds = collect();
        $publicPageableTypes = ResolvePublicPageableMorphTypesAction::run();

        foreach (CapellCore::getPageVariationModels() as $pageClass) {
            if (! is_a($pageClass, Pageable::class, true)
                || ! in_array($pageClass, $publicPageableTypes, true)) {
                continue;
            }

            /** @var class-string<Model&Pageable> $pageClass */
            $pages = $pages->merge(
                SiteScope::applyForCurrentActor(
                    $pageClass::query(),
                    denyWhenMissingActor: true,
                )
                    ->when(
                        $siteId !== null,
                        fn (Builder $query): Builder => $query->where('site_id', $siteId),
                    )
                    ->whereHas(
                        'blueprint',
                        fn (Builder $query): Builder => $query->enabled()->accessible(),
                    )
                    ->publishedDate()
                    ->when(
                        $languageId !== null,
                        fn (Builder $query): Builder => $query->whereHas(
                            'translations',
                            fn (Builder $query): Builder => $query->where('language_id', $languageId),
                        ),
                    )
                    ->whereHas(
                        'pageUrls',
                        function (Builder $query) use ($languageId): Builder {
                            return $query
                                ->where('status', true)
                                ->where(function (Builder $query): void {
                                    $query
                                        ->whereNull('type')
                                        ->orWhere('type', '!=', UrlTypeEnum::Redirect);
                                })
                                ->when(
                                    $languageId !== null,
                                    fn (Builder $query): Builder => $query->where('language_id', $languageId),
                                )
                                ->whereHas(
                                    'siteDomain',
                                    fn (Builder $query): Builder => $query->where('status', true),
                                );
                        },
                    )
                    ->with([
                        'site',
                        'translations',
                        'pageUrls.language',
                        'pageUrls.siteDomain',
                    ])
                    ->get()
                    ->filter(fn (Model $page): bool => $page instanceof Pageable && Gate::allows('view', $page)),
            );
        }

        /** @var Collection<int, Model&Pageable> $pages */
        $impactPages = collect();

        foreach ($pages as $page) {
            $pageData = $this->pageData($page, $languageId);

            if (! $pageData instanceof EditorImpactPageData) {
                continue;
            }

            $impactPages->push($pageData);
            $siteIds->push($page->getAttribute('site_id'));
        }

        /** @var Collection<int, EditorImpactPageData> $impactPages */
        $impactPages = $impactPages
            ->sortBy(fn (EditorImpactPageData $page): string => $page->site . '|' . $page->name . '|' . $page->type)
            ->values();

        return new EditorImpactPreviewData(
            pageCount: $impactPages->count(),
            siteCount: $siteIds->unique()->count(),
            localeCount: $impactPages
                ->flatMap(fn (EditorImpactPageData $page): array => $page->locales)
                ->unique()
                ->count(),
            pages: array_values($impactPages->all()),
        );
    }

    private function pageData(Model&Pageable $page, ?int $languageId): ?EditorImpactPageData
    {
        $site = $page->relationLoaded('site') ? $page->getRelation('site') : null;

        /** @var \Illuminate\Database\Eloquent\Collection<int, PageUrl> $pageUrls */
        $pageUrls = $page->relationLoaded('pageUrls') ? $page->getRelation('pageUrls') : new \Illuminate\Database\Eloquent\Collection;
        $urls = $pageUrls
            ->filter(fn (PageUrl $pageUrl): bool => $pageUrl->getAttribute('site_id') === $page->getAttribute('site_id')
                && (bool) $pageUrl->status
                && ! $pageUrl->isRedirect()
                && ($languageId === null || (int) $pageUrl->language_id === $languageId)
                && $this->hasEnabledSiteDomain($pageUrl)
                && $this->hasTranslation($page, $pageUrl))
            ->map(fn (PageUrl $pageUrl): ?EditorImpactUrlData => $this->urlData($pageUrl))
            ->filter(fn (?EditorImpactUrlData $url): bool => $url instanceof EditorImpactUrlData)
            ->sortBy(fn (EditorImpactUrlData $url): string => $url->locale . '|' . $url->url)
            ->values();

        if ($urls->isEmpty()) {
            return null;
        }

        return new EditorImpactPageData(
            name: $this->stringAttribute($page, 'name'),
            type: class_basename($page),
            site: $site instanceof Site
                ? $this->stringAttribute($site, 'name')
                : (string) __('capell-navigation::generic.impact_preview_unknown_site'),
            locales: array_values($urls
                ->map(fn (EditorImpactUrlData $url): string => $url->locale)
                ->unique()
                ->values()
                ->all()),
            urls: array_values($urls->all()),
        );
    }

    private function hasEnabledSiteDomain(PageUrl $pageUrl): bool
    {
        if (! $pageUrl->relationLoaded('siteDomain')) {
            return false;
        }

        $siteDomain = $pageUrl->getRelation('siteDomain');

        return $siteDomain instanceof SiteDomain && (bool) $siteDomain->status;
    }

    private function hasTranslation(Model&Pageable $page, PageUrl $pageUrl): bool
    {
        if (! $page->relationLoaded('translations')) {
            return false;
        }

        $translations = $page->getRelation('translations');

        return $translations instanceof \Illuminate\Database\Eloquent\Collection
            && $translations->contains(
                fn (Model $translation): bool => $translation instanceof Translation
                    && (int) $translation->language_id === (int) $pageUrl->language_id,
            );
    }

    private function urlData(PageUrl $pageUrl): ?EditorImpactUrlData
    {
        $url = $this->publicUrl($pageUrl);

        return $url === null
            ? null
            : new EditorImpactUrlData(
                locale: $this->locale($pageUrl),
                url: $url,
            );
    }

    private function publicUrl(PageUrl $pageUrl): ?string
    {
        $pagePath = $pageUrl->getAttribute('url');

        if (! is_string($pagePath)
            || ! str_starts_with($pagePath, '/')
            || str_starts_with($pagePath, '//')
            || preg_match('/[\x00-\x1F\x7F]/', $pagePath) === 1) {
            return null;
        }

        $url = PageUrlPresenter::fullUrl($pageUrl);

        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)
            || ! is_string($parts['scheme'] ?? null)
            || ! is_string($parts['host'] ?? null)
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            || $parts['host'] === '') {
            return null;
        }

        $originalPath = (string) ($parts['path'] ?? '');
        $path = preg_replace('#/{2,}#', '/', $originalPath) ?? $originalPath;
        $authority = $parts['host'];

        if (isset($parts['port'])) {
            $authority .= ':' . $parts['port'];
        }

        $normalisedUrl = $parts['scheme'] . '://' . $authority . $path
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');

        return SafeUrl::sanitise($normalisedUrl);
    }

    private function locale(PageUrl $pageUrl): string
    {
        $language = $pageUrl->relationLoaded('language') ? $pageUrl->getRelation('language') : null;
        if (! $language instanceof Language) {
            return '';
        }

        $locale = $language->locale ?: $language->code;

        return $locale !== ''
            ? $locale
            : $language->name;
    }

    private function stringAttribute(Model $model, string $attribute): string
    {
        $value = $model->getAttribute($attribute);

        return is_string($value) ? $value : '';
    }

    private function canUpdateNavigation(Navigation $navigation): bool
    {
        $actor = auth()->user();

        return $actor instanceof Authenticatable
            && Gate::forUser($actor)->allows('update', $navigation);
    }
}
