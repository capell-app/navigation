<?php

declare(strict_types=1);

use Capell\Themes\Agency\Widgets\PortfolioGridWidget;

test('portfolio grid widget exposes projects and filters fields', function (): void {
    $widget = PortfolioGridWidget::make();

    expect($widget->fieldNames())->toContain('title', 'filters', 'projects')
        ->and($widget->view)->toBe('agency::components.portfolio-grid');
});

test('portfolio grid widget render does not throw for empty data', function (): void {
    $widget = PortfolioGridWidget::make();

    expect($widget->render([]))->toBeString();
});

test('portfolio grid default projects include a category key', function (): void {
    $widget = PortfolioGridWidget::make();
    $defaults = $widget->defaults();

    expect($defaults['projects'])->toBeArray()->not->toBeEmpty();
    expect($defaults['projects'][0])->toHaveKey('category');
});
