<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutWidget;

use Capell\Admin\Filament\Components\Forms\HtmlClassInput;
use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Layout\Enums\SchemaEnum;
use Filament\Forms;

class DefaultLayoutWidgetSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::LayoutWidget->value;

    public static function make(Forms\Form $form): array
    {
        return [
            Forms\Components\Checkbox::make('hide_content')
                ->label(__('capell-admin::form.hide_content'))
                ->helperText(__('capell-admin::generic.hide_content_info')),
            HtmlClassInput::make('html_class'),
        ];
    }
}
