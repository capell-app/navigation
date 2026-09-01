<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Actions\ResolvePublicPageableMorphTypesAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Navigation\Data\NavigationStarterItemsData;
use Capell\Navigation\Data\NavigationStarterRequestData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Build starter navigation items from a site's published top-level pages.
 *
 * The result is a plain list of ordinary navigation items — there is no
 * parallel "generated menu" type — so an editor can rename, reorder, nest, or
 * delete every entry afterwards. Pages the current actor cannot view, pages
 * that are unpublished, pages whose page type is disabled or inaccessible, and
 * pages without an active public URL are all excluded.
 *
 * @method static NavigationStarterItemsData run(NavigationStarterRequestData $request)
 */
final class BuildNavigationStarterItemsAction
{
    use AsFake;
    use AsObject;

    public function handle(NavigationStarterRequestData $request): NavigationStarterItemsData
    {
        if ($request->siteId === null || $request->limit <= 0) {
            return new NavigationStarterItemsData;
        }

        $language = $this->resolveLanguage($request->languageId);
        $pages = $this->publishedTopLevelPages($request);

        $items = [];

        foreach ($pages as $page) {
            $items[(string) Str::uuid()] = [
                'label' => $this->pageLabel($page, $language),
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $page->getAttribute('site_id'),
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
                'is_visible' => true,
            ];
        }

        return new NavigationStarterItemsData(
            items: $items,
            pageCount: count($items),
        );
    }

    /**
     * @return Collection<int, Model&Pageable>
     */
    private function publishedTopLevelPages(NavigationStarterRequestData $request): Collection
    {
        /** @var Collection<int, Model&Pageable> $pages */
        $pages = new Collection;

        $publicPageableTypes = ResolvePublicPageableMorphTypesAction::run();

        foreach (CapellCore::getPageVariationModels() as $pageClass) {
            if (! is_a($pageClass, Pageable::class, true)
                || ! in_array($pageClass, $publicPageableTypes, true)) {
                continue;
            }

            /** @var class-string<Model&Pageable> $pageClass */
            $pages = $pages->merge($this->pagesForClass($pageClass, $request));
        }

        return $pages
            ->filter(fn (Model $page): bool => $page instanceof Pageable && Gate::allows('view', $page))
            ->sortBy([
                $this->orderValue(...),
                $this->nameValue(...),
            ])
            ->take($request->limit)
            ->values();
    }

    /**
     * @param  class-string<Model&Pageable>  $pageClass
     * @return Collection<int, Model&Pageable>
     */
    private function pagesForClass(string $pageClass, NavigationStarterRequestData $request): Collection
    {
        $table = (new $pageClass)->getTable();

        /** @var Collection<int, Model&Pageable> $pages */
        $pages = SiteScope::applyForCurrentActor(
            $pageClass::query(),
            denyWhenMissingActor: true,
        )
            ->where('site_id', $request->siteId)
            ->whereHas(
                'blueprint',
                fn (Builder $query): Builder => $query->enabled()->accessible(),
            )
            ->publishedDate()
            ->when(
                $pageClass::hasPageHierarchy() && DatabaseSchema::hasColumn($table, 'parent_id'),
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
            )
            ->when(
                $request->languageId !== null,
                fn (Builder $query): Builder => $query->whereHas(
                    'translations',
                    fn (Builder $query): Builder => $query->where('language_id', $request->languageId),
                ),
            )
            ->whereHas(
                'pageUrls',
                fn (Builder $query): Builder => $query
                    ->where('status', true)
                    ->where(function (Builder $query): void {
                        $query
                            ->whereNull('type')
                            ->orWhere('type', '!=', UrlTypeEnum::Redirect);
                    })
                    ->when(
                        $request->languageId !== null,
                        fn (Builder $query): Builder => $query->where('language_id', $request->languageId),
                    )
                    ->where('page_urls.site_id', $request->siteId)
                    ->whereHas(
                        'siteDomain',
                        fn (Builder $query): Builder => $query->where('status', true),
                    ),
            )
            ->with(['site', 'translations'])
            ->get();

        return $pages;
    }

    private function nameValue(Model $page): string
    {
        $name = $page->getAttribute('name');

        return is_string($name) ? $name : '';
    }

    private function orderValue(Model $page): int
    {
        $order = $page->getAttribute('order');

        return is_numeric($order) ? (int) $order : PHP_INT_MAX;
    }

    private function pageLabel(Model&Pageable $page, ?Language $language): string
    {
        if ($page instanceof Page) {
            return NavigationCreator::getPageNavigationLabel($page, $language);
        }

        $translation = $language instanceof Language
            ? $page->translations->firstWhere('language_id', $language->getKey())
            : null;

        $label = $translation instanceof Model ? $translation->getAttribute('label') : null;

        if (is_string($label) && trim($label) !== '') {
            return trim($label);
        }

        $name = $page->getAttribute('name');

        return is_string($name) ? $name : '';
    }

    private function resolveLanguage(?int $languageId): ?Language
    {
        if ($languageId === null) {
            return null;
        }

        return Language::query()->find($languageId);
    }
}
