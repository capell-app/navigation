<?php

declare(strict_types=1);

namespace Capell\Blog\Support\Sitemap;

use Capell\Blog\Data\ArchiveMonthData;
use Capell\Core\Data\SitemapPageData;
use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Support\Sitemap\AbstractSitemapPages;
use Illuminate\Support\Collection;

class ArchivePageSitemap extends AbstractSitemapPages
{
    public function fetch(): Collection
    {
        /** @var class-string<Page> $model */
        $model = CapellCore::getModel(CoreModelEnum::Page);

        $archivePage = $model::getFirstPageByTypeForSite('archive', $this->site, $this->language);
        if (! $archivePage instanceof Page) {
            return collect([]);
        }

        $archivesPage = $archivePage->parent;

        return collect([
            SitemapPageData::from([
                'label' => $archivesPage->translation->title,
                'url' => $archivePage->pageUrl->full_url,
            ])
                ->toArray(),
        ]);
    }

    public function format(ArchiveMonthData $monthData, Page $archivePage): SitemapPageData
    {
        return SitemapPageData::from([
            'label' => $monthData->getDate()->format('F Y') . ' (' . $monthData->total . ')',
            'url' => $archivePage->pageUrl->full_url . sprintf('/%d-%d', $monthData->year, $monthData->month),
        ]);
    }
}
