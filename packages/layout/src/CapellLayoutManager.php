<?php

declare(strict_types=1);

namespace Capell\Layout;

class CapellLayoutManager
{
    public static function getMigrations(): array
    {
        return [
            'create_widgets_table',
            'create_contents_table',
            'create_content_assets_table',
            'create_widget_assets_table',
        ];
    }
}
