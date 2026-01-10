<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Models\Tag;
use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Collection;

class TagPageSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

        $tagPage = $model::getFirstPageByTypeForSite(
            'tag',
            $this->site,
            $this->language,
            fn ($query) => $query->withWhereHas(
                'parent',
                fn (BuilderContract $query) => $query->with([
                    'pageUrl' => fn ($query) => $query->with('siteDomain')->where('language_id', $this->language->id),
                    'translation' => fn ($query) => $query->where('language_id', $this->language->id),
                ]),
            ),
        );

        if (! $tagPage instanceof Page) {
            return collect([]);
        }

        $tagsPage = $tagPage->parent;

        return collect([
            SitemapPageData::from([
                'label' => $tagsPage->translation->title,
                'url' => $tagsPage->pageUrl->full_url,
                'children' => $this->getTagPages($tagPage),
            ])
                ->toArray(),
        ]);
    }

    public function format(Page $tagPage, Tag $tag): SitemapPageData
    {
        $url = $tagPage->pageUrl->url;

        if (str_ends_with($url, '/*')) {
            $url = mb_substr($url, 0, -2);
        }

        $url .= '/' . $tag->getTranslation('slug', $this->language->code);

        return SitemapPageData::from([
            'label' => $tag->getTranslation('name', $this->language->code) . ' (' . $tag->pages_count . ')',
            'url' => $url,
        ]);
    }

    private function getTagPages(Page $tagPage): Collection
    {
        return TagLoader::getTags(site: $this->site, language: $this->language, limit: 100)
            ->map(fn (Tag $tag): array => $this->format($tagPage, $tag)->toArray());
    }
}
