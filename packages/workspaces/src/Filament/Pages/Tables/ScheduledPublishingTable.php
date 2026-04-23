<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Filament\Resources\Pages\Actions\BulkCancelScheduleBulkAction;
use Capell\Admin\Filament\Resources\Pages\Actions\BulkPublishNowBulkAction;
use Capell\Core\Models\Page;
use Capell\Workspaces\Actions\Reports\BuildScheduledPublishingQueryAction;
use Carbon\CarbonInterface;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduledPublishingTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildScheduledPublishingQueryAction::run())
            ->columns([
                TextColumn::make('name')
                    ->label(__('capell-admin::table.name'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('action')
                    ->label(__('capell-admin::table.action'))
                    ->size('sm')
                    ->badge()
                    ->state(fn (Page $record): string => self::resolveAction($record))
                    ->formatStateUsing(fn (string $state): string => (string) __('capell-admin::table.' . $state)),
                DateColumn::make('visible_from')
                    ->label(__('capell-admin::table.visible_from'))
                    ->size('sm')
                    ->sortable(),
                DateColumn::make('visible_until')
                    ->label(__('capell-admin::table.visible_until'))
                    ->size('sm')
                    ->sortable(),
                TextColumn::make('scheduled_for')
                    ->label(__('capell-admin::table.scheduled_for'))
                    ->size('sm')
                    ->dateTime()
                    ->state(fn (Page $record): ?CarbonInterface => self::resolveScheduledFor($record))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw(
                        "CASE WHEN COALESCE(visible_from, '9999-12-31') < COALESCE(visible_until, '9999-12-31') THEN COALESCE(visible_from, '9999-12-31') ELSE COALESCE(visible_until, '9999-12-31') END " . ($direction === 'asc' ? 'asc' : 'desc'),
                    )),
            ])
            ->toolbarActions([
                BulkPublishNowBulkAction::make(),
                BulkCancelScheduleBulkAction::make(),
            ])
            ->defaultSort(column: 'scheduled_for', direction: 'asc');
    }

    private static function resolveAction(Page $record): string
    {
        $visibleFrom = $record->visible_from;

        if ($visibleFrom !== null && $visibleFrom->isFuture()) {
            return 'publish';
        }

        return 'unpublish';
    }

    private static function resolveScheduledFor(Page $record): ?CarbonInterface
    {
        $visibleFrom = $record->visible_from;
        $visibleUntil = $record->visible_until;

        $fromIsFuture = $visibleFrom !== null && $visibleFrom->isFuture();
        $untilIsFuture = $visibleUntil !== null && $visibleUntil->isFuture();

        if ($fromIsFuture && $untilIsFuture) {
            return $visibleFrom->lessThan($visibleUntil) ? $visibleFrom : $visibleUntil;
        }

        if ($fromIsFuture) {
            return $visibleFrom;
        }

        if ($untilIsFuture) {
            return $visibleUntil;
        }

        return null;
    }
}
