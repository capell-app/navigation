<?php

declare(strict_types=1);

namespace Capell\Plugins\Enums;

enum PluginKind: string
{
    case Full = 'full';
    case Integration = 'integration';
    case PageType = 'page_type';
    case Theme = 'theme';
}
