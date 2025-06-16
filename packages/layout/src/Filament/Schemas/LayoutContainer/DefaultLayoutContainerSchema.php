<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Schemas\LayoutContainer;

use Capell\Admin\Filament\Components\Forms\ColumnSpanInput;
use Capell\Admin\Filament\Components\Forms\ContainerWidthSelect;
use Capell\Admin\Filament\Components\Forms\HtmlClassInput;
use Capell\Admin\Filament\Components\Forms\MarginSelect;
use Capell\Admin\Filament\Components\Forms\PaddingSelect;
use Capell\Admin\Filament\Components\Forms\SpacingSelect;
use Capell\Admin\Filament\Schemas\AbstractSchema;
use Capell\Layout\Enums\SchemaEnum;
use Filament\Forms;

class DefaultLayoutContainerSchema extends AbstractSchema
{
    protected static string $schemaType = SchemaEnum::LayoutContainer->value;

    public static function make(Forms\Form $form): array
    {
        return [
            Forms\Components\Group::make()
                ->statePath('meta')
                ->columns()
                ->schema([
                    ColumnSpanInput::make(),
                    Forms\Components\Grid::make(['md' => 2])
                        ->visibleOn(['edit', 'editOption'])
                        ->schema([
                            ContainerWidthSelect::make('container'),
                            HtmlClassInput::make('html_class'),
                            Forms\Components\Grid::make(['md' => 3])
                                ->columnSpanFull()
                                ->schema([
                                    PaddingSelect::make('padding'),
                                    MarginSelect::make('margin'),
                                    SpacingSelect::make('spacing'),
                                ]),
                            Forms\Components\TextInput::make('override_columns')
                                ->label(__('capell-admin::form.override_columns'))
                                ->helperText(__('capell-admin::generic.override_columns_info')),
                        ]),
                ]),
        ];
    }
}
