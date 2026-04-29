<?php

declare(strict_types=1);

namespace Capell\AuthenticationLog\Filament\Resources\AuthenticationLogs\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\AuthenticationLog\Models\AuthenticationLog;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AuthenticationLogsTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['authenticatable']))
            ->defaultSort('login_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters(self::getTableFilters());
    }

    protected static function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            TextColumn::make('authenticatable')
                ->label(__('capell-admin::table.user_who_logged_in'))
                ->formatStateUsing(function (?string $state, AuthenticationLog $record): HtmlString {
                    if ($record->authenticatable_id === null) {
                        return new HtmlString('&mdash;');
                    }

                    $url = route(
                        'filament.' . Filament::getCurrentOrDefaultPanel()->getId() . '.resources.' . Str::plural(Str::lower(class_basename($record->authenticatable::class))) . '.edit',
                        ['record' => $record->authenticatable_id],
                    );

                    $name = $record->authenticatable->getAttribute('name');

                    return new HtmlString('<a href="' . $url . '" class="inline-flex items-center justify-center hover:underline focus:outline-none focus:underline filament-tables-link text-primary-600 hover:text-primary-500 text-sm font-medium filament-tables-link-action">' . $name . '</a>');
                })
                ->sortable(),
            TextColumn::make('ip_address')
                ->label(trans('filament-authentication-log::filament-authentication-log.column.ip_address'))
                ->searchable()
                ->sortable(),
            TextColumn::make('user_agent')
                ->label(trans('filament-authentication-log::filament-authentication-log.column.user_agent'))
                ->searchable()
                ->sortable()
                ->wrap()
                ->limit(50)
                ->tooltip(function (TextColumn $column): ?string {
                    $state = $column->getState();

                    if (mb_strlen($state) <= $column->getCharacterLimit()) {
                        return null;
                    }

                    return $state;
                })
                ->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('login_at')
                ->label(trans('filament-authentication-log::filament-authentication-log.column.login_at'))
                ->icon(fn (AuthenticationLog $record): string => $record->login_successful ? 'heroicon-s-check-circle' : 'heroicon-s-x-circle'),
            DateColumn::make('logout_at')
                ->label(trans('filament-authentication-log::filament-authentication-log.column.logout_at'))
                ->icon(fn (AuthenticationLog $record): string => $record->cleared_by_user ? 'heroicon-s-check-circle' : 'heroicon-s-x-circle')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('location')
                ->label(__('capell-admin::table.location'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Filter::make('login_successful')
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->where('login_successful', true)),
            Filter::make('login_at')
                ->schema([
                    DatePicker::make('login_from')
                        ->label(__('capell-admin::filter.login_from')),
                    DatePicker::make('login_until')
                        ->label(__('capell-admin::filter.login_until')),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when(
                        $data['login_from'],
                        fn (Builder $query, string $date): Builder => $query->whereDate('login_at', '>=', $date),
                    )
                    ->when(
                        $data['login_until'],
                        fn (Builder $query, string $date): Builder => $query->whereDate('login_at', '<=', $date),
                    )),
            Filter::make('cleared_by_user')
                ->toggle()
                ->query(fn (Builder $query): Builder => $query->where('cleared_by_user', true)),
        ];
    }
}
