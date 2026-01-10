<?php

declare(strict_types=1);

namespace Capell\Layout\Support\Interceptors\Layouts;

use Capell\Admin\Contracts\LayoutInterceptorInterface;
use Capell\Admin\Data\LayoutData;
use Capell\Core\Models\Layout;
use Capell\Layout\Support\Creator\WidgetCreator;

final class HomeInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(LayoutData $data): LayoutData
    {
        return $data;
    }

    public function afterCreated(Layout $layout, LayoutData $data): void
    {
        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->pageContentWidget();

        $layout->update([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => 'page-content'],
                    ],
                ],
            ],
        ]);
    }
}
