<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Creator;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\AddPageToNavigationAction;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Collection;
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
            ->whereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query->default()->enabled()->accessible()->hiddenSystemGroup(),
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
    }

    public function setupFooterNavigation(Site $site, Language $language): void
    {
        $pages = Page::query()
            ->whereHas(
                'type',
                fn (BuilderContract $query): BuilderContract => $query->default()->enabled()->accessible()->hiddenSystemGroup(),
            )
            ->with('children')
            ->withWhereHas(
                'translations',
                fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
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
            items: $this->buildNavigationPageItems($pages, $language),
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
     * @param  SupportCollection<array-key, mixed>  $pages
     * @return array<array-key, mixed>
     */
    private function buildNavigationPageItems(SupportCollection $pages, Language $language): array
    {
        $this->loadPageTranslations($pages, $language);

        $items = [];

        foreach ($pages as $page) {
            $items[(string) Str::uuid()] = [
                'label' => NavigationCreator::getPageNavigationLabel($page, $language),
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'site_id' => $page->site_id,
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => $page->relationLoaded('children')
                    ? $this->buildNavigationPageItems($page->children, $language)
                    : [],
            ];
        }

        return $items;
    }

    /** @return array{0: int, 1: string} */
    private function mainNavigationSortKey(Page $page, Language $language): array
    {
        $page->loadMissing([
            'translations' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
        ]);

        $label = NavigationCreator::getPageNavigationLabel($page, $language);

        return [
            self::MainNavigationPriority[$label] ?? 60,
            mb_strtolower($label),
        ];
    }

    /**
     * @param  SupportCollection<array-key, mixed>  $pages
     */
    private function loadPageTranslations(SupportCollection $pages, Language $language): void
    {
        if ($pages instanceof Collection) {
            $pages->loadMissing([
                'translations' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
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
}
