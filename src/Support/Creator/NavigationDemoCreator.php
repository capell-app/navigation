<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Creator;

use Aimeos\Nestedset\QueryBuilder as NestedSetQueryBuilder;
use Capell\Core\Actions\ResolvePageableMorphModelAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class NavigationDemoCreator
{
    /** @var array<string, int> */
    private const array MainNavigationPriority = [
        'Services' => 10,
        'Pricing' => 20,
        'Projects' => 30,
        'Blog' => 40,
        'Resources' => 50,
        'Contact' => 90,
    ];

    public function setupInitialSiteNavigation(Site $site, Page $home, Page $sitemapPage): void
    {
        /** @var class-string<Blueprint> $typeModel */
        $typeModel = Blueprint::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->mainNavigation(site: $site, type: $navigationType, home: $home);

        resolve(NavigationCreator::class)->footerNavigation(
            site: $site,
            type: $navigationType,
            pages: new Collection([$home]),
        );

        resolve(NavigationCreator::class)->subFooterNavigation(
            site: $site,
            type: $navigationType,
            pages: new Collection([$sitemapPage]),
        );
    }

    public function updateRelatedSiteNavigations(): void
    {
        Site::query()->with(['related', 'related.translation'])->get()
            ->each(function (Site $site): void {
                $relatedSites = $site->getRelationValue('related');

                if (! $relatedSites instanceof SupportCollection) {
                    return;
                }

                $this->updateSubFooterNavigation($site, $relatedSites);
            });
    }

    public function setupMainNavigation(Site $site, Language $language, Page $home): void
    {
        $pages = Page::query()
            ->where(fn (NestedSetQueryBuilder $query): NestedSetQueryBuilder => $this->publicNavigationPageQuery($query))
            ->whereHas(
                'blueprint',
                fn (Builder $query): Builder => $query->default()->enabled()->accessible()->hiddenSystemGroup(),
            )
            ->with('children')
            ->where('site_id', $site->id)
            ->whereNull('parent_id')
            ->notHomePage()
            ->publishedDate()
            ->get()
            ->sortBy(fn (Page $page): array => $this->mainNavigationSortKey($page, $language))
            ->take(6);

        /** @var class-string<Blueprint> $typeModel */
        $typeModel = Blueprint::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->mainNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
            home: $home,
            additionalItems: $this->buildNavigationPageItems($pages, $language),
        );

        $this->removeNonPublicPageItems(
            Navigation::query()
                ->where('site_id', $site->id)
                ->where('key', NavigationHandle::Main->value)
                ->where('language_id', $language->id)
                ->firstOrFail(),
            $language,
        );
    }

    public function setupFooterNavigation(Site $site, Language $language): void
    {
        $pages = Page::query()
            ->where(fn (NestedSetQueryBuilder $query): NestedSetQueryBuilder => $this->publicNavigationPageQuery($query))
            ->whereHas(
                'blueprint',
                fn (Builder $query): Builder => $query->default()->enabled()->accessible()->hiddenSystemGroup(),
            )
            ->with('children')
            ->withWhereHas(
                'translations',
                fn (Builder|Relation $query): mixed => $query
                    ->where('language_id', $language->id)
                    ->where(function (Builder $query): void {
                        $this->applyFooterNavigationTranslationQuery($query);
                    }),
            )
            ->where('site_id', $site->id)
            ->notHomePage()
            ->publishedDate()
            ->limit(8)
            ->get()
            ->toTree();

        $pages = $pages instanceof SupportCollection ? $pages : new SupportCollection($pages);

        /** @var class-string<Blueprint> $typeModel */
        $typeModel = Blueprint::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->footerNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
            items: $this->buildNavigationPageItems($pages, $language, true),
        );

        $this->removeNonPublicPageItems(
            Navigation::query()
                ->where('site_id', $site->id)
                ->where('key', NavigationHandle::Footer->value)
                ->where('language_id', $language->id)
                ->firstOrFail(),
            $language,
            true,
        );
    }

    public function setupSubFooterNavigation(Site $site, ?Language $language): void
    {
        /** @var class-string<Blueprint> $typeModel */
        $typeModel = Blueprint::class;
        $navigationType = $typeModel::query()->navigationType()->default()->first();

        resolve(NavigationCreator::class)->subFooterNavigation(
            site: $site,
            type: $navigationType,
            language: $language,
        );
    }

    /** @param SupportCollection<int, Site> $relatedSites */
    public function updateSubFooterNavigation(Site $site, SupportCollection $relatedSites): void
    {
        Navigation::query()
            ->where('site_id', $site->id)
            ->where('key', NavigationHandle::SubFooter->value)
            ->each(fn (Navigation $navigation) => $relatedSites->each(
                function (Site $relatedSite) use ($navigation): void {
                    $homepage = Page::getSiteHomePage($relatedSite);

                    if (! $homepage instanceof Pageable) {
                        return;
                    }

                    AddPageToNavigationAction::run(
                        page: $homepage,
                        navigation: $navigation,
                        label: (string) ($relatedSite->translation->label ?? $relatedSite->name),
                    );
                },
            ));
    }

    /**
     * @param  iterable<array-key, Page>  $pages
     * @return array<array-key, array<string, mixed>>
     */
    private function buildNavigationPageItems(iterable $pages, Language $language, bool $footer = false): array
    {
        $this->loadPageTranslations($pages, $language);

        $items = [];

        foreach ($pages as $page) {
            $page->loadMissing('site');

            if (! $this->isPublicNavigationPage($page, $footer)) {
                continue;
            }

            $items[(string) Str::uuid()] = [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $page->site_id,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => $page->relationLoaded('children')
                    ? $this->buildNavigationPageItems($page->children, $language, $footer)
                    : [],
            ];
        }

        return $items;
    }

    /** @return array{0: int, 1: string} */
    private function mainNavigationSortKey(Page $page, Language $language): array
    {
        $page->loadMissing([
            'site',
            'translations' => fn (Relation $query): Relation => $query->where('language_id', $language->id),
        ]);

        $label = NavigationCreator::getPageNavigationLabel($page, $language);

        return [
            self::MainNavigationPriority[$label] ?? 60,
            mb_strtolower($label),
        ];
    }

    /**
     * @param  iterable<array-key, mixed>  $pages
     */
    private function loadPageTranslations(iterable $pages, Language $language): void
    {
        if ($pages instanceof Collection) {
            $pages->loadMissing([
                'translations' => fn (Relation $query): Relation => $query->where('language_id', $language->id),
            ]);
        }

        foreach ($pages as $page) {
            if (! $page instanceof Page) {
                continue;
            }

            if (! $page->relationLoaded('children')) {
                continue;
            }

            $children = $page->children;
            if (! $children instanceof Collection) {
                continue;
            }

            if ($children->isEmpty()) {
                continue;
            }

            $this->loadPageTranslations($children, $language);
        }
    }

    /**
     * @param  NestedSetQueryBuilder<Page>  $query
     * @return NestedSetQueryBuilder<Page>
     */
    private function publicNavigationPageQuery(NestedSetQueryBuilder $query): NestedSetQueryBuilder
    {
        return $query->where(fn (NestedSetQueryBuilder $query): NestedSetQueryBuilder => $query->whereNull('pages.meta')
            ->orWhere(fn (NestedSetQueryBuilder $query): NestedSetQueryBuilder => $query
                ->whereJsonDoesntContainKey('pages.meta->demo_fixture')
                ->whereJsonDoesntContainKey('pages.meta->theme_demo')
                ->where(fn (NestedSetQueryBuilder $query): NestedSetQueryBuilder => $query
                    ->whereJsonDoesntContainKey('pages.meta->navigation.exclude')
                    ->orWhereJsonDoesntContain('pages.meta->navigation.exclude', true))));
    }

    /**
     * @param Builder<*> $query
     */
    private function applyFooterNavigationTranslationQuery(Builder $query): void
    {
        $query->where(fn (Builder $query): Builder => $query->whereNull('translations.meta')
            ->orWhereJsonDoesntContainKey('translations.meta->exclude_from_footer')
            ->orWhereJsonDoesntContain('translations.meta->exclude_from_footer', true))->where(fn (Builder $query): Builder => $query->whereNull('translations.meta')
            ->orWhereJsonDoesntContainKey('translations.meta->exclude_from_navigation')
            ->orWhereJsonDoesntContain('translations.meta->exclude_from_navigation', true));
    }

    private function removeNonPublicPageItems(Navigation $navigation, Language $language, bool $footer = false): void
    {
        $navigation->items = $this->filterPublicNavigationItems(
            collect($navigation->items),
            $language,
            $footer,
        )->all();
        $navigation->save();
    }

    /**
     * @param  SupportCollection<array-key, mixed>  $items
     * @return SupportCollection<array-key, mixed>
     */
    private function filterPublicNavigationItems(
        SupportCollection $items,
        Language $language,
        bool $footer,
    ): SupportCollection {
        $filteredItems = [];

        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                $filteredItems[$key] = $item;

                continue;
            }

            $data = $item['data'] ?? null;

            if (
                is_array($data)
                && isset($data['pageable_id'], $data['pageable_type'])
                && is_string($data['pageable_type'])
                && (is_int($data['pageable_id']) || is_string($data['pageable_id']))
            ) {
                $page = ResolvePageableMorphModelAction::run(
                    $data['pageable_type'],
                    $data['pageable_id'],
                );

                if ($page instanceof Page) {
                    $page->load([
                        'site',
                        'translations' => fn (Relation $query): Relation => $query
                            ->where('language_id', $language->id),
                    ]);

                    if (! $this->isPublicNavigationPage($page, $footer)) {
                        continue;
                    }
                }
            }

            if (is_array($item['children'] ?? null) && $item['children'] !== []) {
                $item['children'] = $this->filterPublicNavigationItems(
                    collect($item['children']),
                    $language,
                    $footer,
                )->all();
            }

            $filteredItems[$key] = $item;
        }

        return new SupportCollection($filteredItems);
    }

    private function isPublicNavigationPage(Page $page, bool $footer = false): bool
    {
        $translation = $page->relationLoaded('translation')
            ? $page->getRelation('translation')
            : ($page->relationLoaded('translations') ? $page->translations->first() : null);

        if (! $translation instanceof Translation) {
            $translation = null;
        }

        return data_get($page->meta, 'demo_fixture') === null
            && data_get($page->meta, 'theme_demo') === null
            && data_get($page->meta, 'navigation.exclude') !== true
            && data_get($translation?->meta, 'exclude_from_navigation') !== true
            && (! $footer || data_get($translation?->meta, 'exclude_from_footer') !== true);
    }
}
