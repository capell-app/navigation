<?php

declare(strict_types=1);

namespace Capell\Hero\Actions;

use Capell\Core\Models\Layout;
use Capell\Layout\Models\Widget;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Layout $layout)
 */
class AddHeroToLayoutAction
{
    use AsFake;
    use AsObject;

    public function handle(Layout $layout): void
    {
        $heroWidget = CreateHeroWidgetAction::run();

        $containers = $layout->containers ?? [];

        if (array_key_exists('hero', $containers)) {
            return;
        }

        $containers = array_merge(['hero' => $this->heroContainer($heroWidget)], $containers);

        $layout->update(['containers' => $containers]);
    }

    private function heroContainer(Widget $widget): array
    {
        return [
            'meta' => [
                'colspan' => 12,
                'container' => 'full',
            ],
            'widgets' => [
                ['widget_key' => $widget->key],
            ],
        ];
    }
}
