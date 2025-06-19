<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;
use Capell\Layout\Enums\LayoutModelEnum;
use Filament\Forms;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class LayoutResource extends \Capell\Admin\Filament\Resources\LayoutResource
{
    protected static function getTableActions(): array
    {
        return [
            self::getLayoutInfoAction(),
            ...parent::getTableActions(),
        ];
    }

    protected static function getLayoutInfoAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('info')
            ->label(__('capell-admin::button.info'))
            ->icon('heroicon-o-information-circle')
            ->iconButton()
            ->color('info')
            ->infolist(fn (Layout $record): array => [
                ViewEntry::make('widgets')
                    ->view(
                        'capell-layout::components.infolists.entries.layout-widgets',
                        [
                            'widgets' => $record->layoutWidgets,
                        ]
                    ),
            ]);
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\Filter::make('filter')
                ->form([
                    Forms\Components\Select::make('widget_key')
                        ->label(__('capell-admin::form.widget'))
                        ->options(fn () => CapellCore::getModel(LayoutModelEnum::Widget->name)::getOptions('key', 'name')),
                ])
                ->modifyBaseQueryUsing(fn (Builder $query, array $data) => $query
                    ->when(
                        $data['widget_key'],
                        fn (Builder $query, $widgetKey): Builder => $query->whereJsonContains('widgets', $widgetKey),
                    ))
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (! empty($data['widget_id'])) {
                        $indicators['widget_id'] = __(
                            'capell-admin::filter.widget',
                            ['search' => CapellCore::getModel(LayoutModelEnum::Widget->name)::find($data['widget_id'], 'name')?->name]
                        );
                    }

                    return $indicators;
                }),
            ...parent::getTableFilters(),
        ];
    }
}
