<?php

declare(strict_types=1);

namespace Capell\Blog\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class ArticleHealthData extends Data
{
    /**
     * @param  DataCollection<int, TagCountData>  $topTags
     * @param  DataCollection<int, LanguageCoverageData>  $languageCoverage
     */
    public function __construct(
        public readonly int $totalArticles,
        public readonly int $totalTags,
        public readonly int $totalCategories,
        public readonly int $publishedCount,
        public readonly int $draftCount,
        public readonly int $scheduledFutureCount,
        public readonly int $expiredCount,
        public readonly int $recentlyCreatedCount,
        public readonly int $recentlyUpdatedCount,
        public readonly DataCollection $topTags,
        public readonly DataCollection $languageCoverage,
    ) {}
}

final class TagCountData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $articleCount,
    ) {}
}

final class LanguageCoverageData extends Data
{
    public function __construct(
        public readonly string $language,
        public readonly int $withTranslation,
        public readonly int $withoutTranslation,
        public readonly int $total,
    ) {}
}
