<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Models\Collection;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;

enum ModelEnum: string
{
    case Content = Collection::class;

    case Widget = Widget::class;

    case WidgetAsset = WidgetAsset::class;
}
