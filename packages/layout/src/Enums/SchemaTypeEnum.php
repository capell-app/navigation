<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

enum SchemaTypeEnum: string
{
    case Content = 'Contents';
    case Layout = 'Layouts';
    case LayoutContainer = 'LayoutContainers';
    case LayoutWidget = 'LayoutWidgets';
    case Widget = 'Widgets';
    case WidgetAsset = 'WidgetAssets';
}
