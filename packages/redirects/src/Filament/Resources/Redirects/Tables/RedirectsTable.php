<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Resources\Redirects\Tables;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Enums\RedirectStatusCodeEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RedirectsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with(['language', 'site']),
            )
            ->defaultSort('created_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters([
                SelectFilter::make('status_code')
                    ->label(__('redirects::table.status_code'))
                    ->options(RedirectStatusCodeEnum::class),
                TernaryFilter::make('is_manual')
                    ->label(__('redirects::table.is_manual'))
                    ->trueLabel(__('redirects::generic.manual'))
                    ->falseLabel(__('redirects::generic.auto'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('is_manual', true),
                        false: fn (Builder $query): Builder => $query->where('is_manual', false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('site_id')
                    ->label(__('redirects::form.site'))
                    ->relationship(
                        name: 'site',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => SiteScope::applyForCurrentActor($query, 'id'),
                    ),
                SelectFilter::make('language_id')
                    ->label(__('redirects::form.language'))
                    ->relationship(name: 'language', titleAttribute: 'name'),
                TernaryFilter::make('status')
                    ->label(__('redirects::table.status'))
                    ->trueLabel(__('redirects::generic.active'))
                    ->falseLabel(__('redirects::generic.disabled'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('status', true),
                        false: fn (Builder $query): Builder => $query->where('status', false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TrashedFilter::make(),
            ])
            ->filtersFormColumns(4)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->recordActions([
                EditAction::make()
                    ->modalHeading(__('redirects::generic.edit_redirect'))
                    ->visible(fn (PageUrl $record): bool => $record->is_manual),
                ActionGroup::make([
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->emptyStateHeading(__('redirects::generic.no_redirects'))
            ->emptyStateDescription(__('redirects::generic.no_redirects_description'))
            ->emptyStateIcon('heroicon-o-arrow-path');
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            TextColumn::make('url')
                ->label(__('redirects::table.source_url'))
                ->sortable()
                ->searchable()
                ->size('sm')
                ->wrap(),
            TextColumn::make('target_url')
                ->label(__('redirects::table.target_url'))
                ->searchable()
                ->size('sm')
                ->wrap()
                ->description(fn (PageUrl $record): ?string => $record->pageable?->name)
                ->placeholder(__('redirects::generic.auto_resolved')),
            TextColumn::make('status_code')
                ->label(__('redirects::table.status_code'))
                ->badge()
                ->sortable(),
            TextColumn::make('is_manual')
                ->label(__('redirects::table.is_manual'))
                ->formatStateUsing(fn (bool $state): string => $state
                    ? __('redirects::generic.manual')
                    : __('redirects::generic.auto'))
                ->badge()
                ->color(fn (bool $state): string => $state ? 'primary' : 'gray')
                ->toggleable(),
            LanguageColumn::make('language')
                ->toggleable(),
            StatusIconColumn::make('status')
                ->toggleable(false),
            TextColumn::make('hit_count')
                ->label(__('redirects::table.hit_count'))
                ->sortable()
                ->numeric()
                ->toggleable(),
            DateColumn::make('last_hit_at')
                ->label(__('redirects::table.last_hit_at'))
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('creator.name')
                ->label(__('redirects::table.created_by'))
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('created_at')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
