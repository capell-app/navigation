<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Events\Tables;

use Capell\Admin\Enums\FilamentColorEnum;
use Capell\Admin\Filament\Actions\Table\ReplicatePageAction;
use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeColumn;
use Capell\Admin\Filament\Components\Tables\Filters\DateFilter;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Support\Loader\SiteLoader;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Actions\PageDeletedAction;
use Capell\Core\Contracts\Pageable;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class EventPagesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static::getTableQuery(...))
            ->defaultSort('updated_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->recordClasses(fn (Pageable $record): ?string => $record->deleted_at !== null ? 'table-row-warning' : null)
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicatePageAction::make(),
                    DeleteAction::make()
                        ->after(function (Pageable $record): void {
                            PageDeletedAction::run($record);
                        }),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->recordUrl(fn (Pageable $record): ?string => GetEditPageResourceUrlAction::run($record));
    }

    protected static function getTableQuery(Builder $query): Builder
    {
        return $query
            ->whereHas('site', fn (BuilderContract $query): BuilderContract => $query->withTrashed())
            ->whereHas('type')
            ->with([
                'creator',
                'editor',
                'image',
                'nextOccurrence',
                'site' => fn (BuilderContract $query): BuilderContract => $query->withTrashed(),
                'translations.language',
                'type',
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            PageNameColumn::make('name')
                ->wrap()
                ->sortable()
                ->children(false)
                ->ancestors(false)
                ->searchable(
                    query: fn (Builder $query, string $search): Builder => $query->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas(
                            'translations',
                            fn (BuilderContract $query): BuilderContract => $query->where('title', 'like', sprintf('%%%s%%', $search)),
                        ),
                )
                ->toggleable(),
            SiteColumn::make('site.name')
                ->color(FilamentColorEnum::LightGray->value)
                ->hidden(
                    fn (HasTable $livewire): bool => (($livewire instanceof ListRecords && $livewire->activeTab !== null)
                            && ! in_array($livewire->getTableFilterState('site_id'), [null, []], true))
                        || SiteLoader::getTotalSites() <= 1,
                ),
            TextColumn::make('nextOccurrence.starts_at')
                ->label(__('capell-events::table.next_occurrence'))
                ->dateTime()
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('meta.location.name')
                ->label(__('capell-events::table.location'))
                ->placeholder('-')
                ->toggleable(),
            MediaLibraryImageColumn::make('image')
                ->collection('image')
                ->toggleable()
                ->alignCenter()
                ->width(0),
            LanguagesColumn::make('translations.language'),
            TypeColumn::make('type.name')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('creator.name')
                ->label(__('capell-admin::table.created_by'))
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('created_at'),
            DateColumn::make('updated_at'),
            DateColumn::make('deleted_at'),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            SelectFilter::make('site_id')
                ->label(__('capell-admin::form.site'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'site',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->ordered(),
                ),
            SelectFilter::make('type_id')
                ->label(__('capell-admin::form.page_type'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->enabled()->pageType(),
                ),
            DateFilter::make('visible_from')
                ->label(__('capell-admin::form.publish_date')),
            TrashedFilter::make()
                ->native(false),
        ];
    }
}
