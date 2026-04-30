<?php

declare(strict_types=1);

use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Analytics\Filament\Settings\Contributors\AnalyticsDashboardSettingsContributor;
use Capell\Analytics\Filament\Widgets\AnalyticsOverviewStatsWidget;
use Capell\Analytics\Filament\Widgets\PopularPagesWidget;
use Capell\Analytics\Filament\Widgets\RecentJourneysWidget;
use Capell\Analytics\Filament\Widgets\TopActionsWidget;
use Capell\Analytics\Filament\Widgets\TrendingPagesWidget;
use Capell\Analytics\Tests\AnalyticsTestCase;
use Livewire\Livewire;

uses(AnalyticsTestCase::class);

it('exposes analytics dashboard settings keys with translated labels', function (): void {
    $entries = (new AnalyticsDashboardSettingsContributor)->settingsKeys();

    expect(collect($entries)->pluck('key')->all())->toBe([
        'analytics_overview',
        'analytics_popular_pages',
        'analytics_trending_pages',
        'analytics_recent_journeys',
        'analytics_top_actions',
    ]);

    foreach ($entries as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('')
            ->and($entry['group'])->toBeString()->not->toBe('');
    }
});

it('registers the analytics dashboard settings contributor', function (): void {
    $contributors = collect(app()->tagged(DashboardSettingsContributor::TAG))
        ->map(fn (DashboardSettingsContributor $contributor): string => $contributor::class);

    expect($contributors)->toContain(AnalyticsDashboardSettingsContributor::class);
});

it('renders analytics dashboard widgets', function (string $widgetClass): void {
    Livewire::test($widgetClass)->assertOk();
})->with([
    AnalyticsOverviewStatsWidget::class,
    PopularPagesWidget::class,
    TrendingPagesWidget::class,
    RecentJourneysWidget::class,
    TopActionsWidget::class,
]);
