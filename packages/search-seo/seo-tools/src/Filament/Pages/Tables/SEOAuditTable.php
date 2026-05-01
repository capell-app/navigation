<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Page;
use Capell\SeoTools\Actions\Reports\BuildSEOAuditQueryAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SEOAuditTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BuildSEOAuditQueryAction::run())
            ->columns([
                PageNameColumn::make('name')
                    ->label(__('capell-admin::table.name'))
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label(__('capell-admin::table.site'))
                    ->size('sm')
                    ->sortable(),
                TextColumn::make('seo_issues')
                    ->label(__('capell-admin::table.seo_issues'))
                    ->size('sm')
                    ->formatStateUsing(fn (Page $record): string => implode(', ', array_filter([
                        ($record->meta_title === null || $record->meta_title === '' || strlen($record->meta_title) < 30) ? __('capell-admin::badge.no_title') : null,
                        ($record->meta_description === null || $record->meta_description === '' || strlen($record->meta_description) < 50) ? __('capell-admin::badge.no_description') : null,
                        ($record->og_image === null || $record->og_image === '') ? __('capell-admin::badge.no_og_image') : null,
                    ], fn (mixed $value): bool => $value !== null)))
                    ->html(),
                TextColumn::make('user.name')
                    ->label(__('capell-admin::table.author'))
                    ->size('sm')
                    ->sortable(),
                DateColumn::make('created_at')
                    ->label(__('capell-admin::table.created_at'))
                    ->size('sm')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
