<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Interceptors\Layouts;

use Capell\Core\Contracts\LayoutInterceptorInterface;
use Capell\Core\Data\LayoutData;
use Capell\Core\Models\Layout;
use Capell\Layout\Support\Creator\WidgetCreator;

final class DefaultInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(LayoutData $data): LayoutData
    {
        return $data;
    }

    public function afterCreated(Layout $layout, LayoutData $data): void
    {
        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->pageContentWidget();
        $widgetCreator->childrenWidget();
        $widgetCreator->latestPagesWidget();

        $layout->update([
            'containers' => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'children'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ]);
    }

    private function sidebarContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'widgets' => $widgets,
        ];
    }

    private function mainContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 9,
            ],
            'widgets' => $widgets,
        ];
    }
}
