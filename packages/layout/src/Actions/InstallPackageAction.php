<?php

declare(strict_types=1);

namespace Capell\Layout\Actions;

use Capell\Admin\Support\Creator\LayoutCreator;
use Capell\Core\Models\Language;
use Capell\Layout\Support\Creator\TypeCreator;
use Capell\Layout\Support\Creator\WidgetCreator;
use Capell\Layout\Support\LayoutModelRegistrar;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run()
 */
class InstallPackageAction
{
    use AsObject;

    public function handle(): void
    {
        LayoutModelRegistrar::register();

        $typeCreator = resolve(TypeCreator::class);
        $typeCreator->createWidgetTypes();

        $typeCreator->createDefaultContentType();
        $typeCreator->createBuilderContentType();

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->createWidgets(Language::all());

        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->setup();
    }
}
