<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class LayoutHealthData extends Data
{
    /**
     * @param  DataCollection<int, WidgetGroupData>  $widgetsByGroup
     * @param  DataCollection<int, UnusedWidgetData>  $unusedWidgets
     * @param  DataCollection<int, LeastUsedWidgetData>  $leastUsedWidgets
     */
    public function __construct(
        public readonly int $totalWidgets,
        public readonly int $totalSections,
        public readonly int $publishedSections,
        public readonly int $draftSections,
        public readonly int $layoutsWithModifications,
        public readonly DataCollection $widgetsByGroup,
        public readonly DataCollection $unusedWidgets,
        public readonly DataCollection $leastUsedWidgets,
    ) {}
}

final class WidgetGroupData extends Data
{
    public function __construct(
        public readonly string $group,
        public readonly int $count,
        public readonly int $published,
        public readonly int $pending,
        public readonly int $expired,
    ) {}
}

final class UnusedWidgetData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $group,
    ) {}
}

final class LeastUsedWidgetData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $layoutCount,
        public readonly string $group,
    ) {}
}
