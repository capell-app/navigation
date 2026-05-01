<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Pages\Tables;

use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\SeoTools\Actions\BuildPageSeoReportAction;
use Capell\SeoTools\Actions\Reports\BuildSEOAuditQueryAction;
use Capell\SeoTools\Data\PageSeoReportData;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class SEOAuditTable implements TableConfigurator
{
    /**
     * @var array<string, PageSeoReportData|null>
     */
    private static array $reports = [];

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
                TextColumn::make('seo_score')
                    ->label(__('capell-seo-tools::generic.seo_panel_score'))
                    ->state(fn (Page $record): ?int => self::reportFor($record)?->score)
                    ->size('sm')
                    ->sortable(false),
                TextColumn::make('critical_issues_count')
                    ->label(__('capell-seo-tools::generic.seo_severity_critical'))
                    ->state(fn (Page $record): ?int => self::reportFor($record)?->criticalCount())
                    ->size('sm')
                    ->sortable(false),
                TextColumn::make('warning_issues_count')
                    ->label(__('capell-seo-tools::generic.seo_severity_warning'))
                    ->state(fn (Page $record): ?int => self::reportFor($record)?->warningCount())
                    ->size('sm')
                    ->sortable(false),
                TextColumn::make('schema_status')
                    ->label(__('capell-seo-tools::generic.seo_check_schema'))
                    ->size('sm')
                    ->state(fn (Page $record): ?string => self::schemaStatusFor($record))
                    ->badge()
                    ->sortable(false),
                TextColumn::make('search_preview_title')
                    ->label(__('capell-seo-tools::generic.seo_panel_search_preview'))
                    ->state(fn (Page $record): ?string => self::reportFor($record)?->searchPreview->title)
                    ->limit(60)
                    ->size('sm')
                    ->sortable(false),
                TextColumn::make('creator.name')
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

    private static function reportFor(Page $record): ?PageSeoReportData
    {
        $record->loadMissing([
            'site.language',
            'translation.language',
            'translations.language',
        ]);

        $site = $record->site;
        $language = self::auditLanguageFor($record) ?? $record->translation?->language ?? $site?->language;
        $cacheKey = sprintf('%s:%s', $record->getKey(), $language?->getKey() ?? 'none');

        if (array_key_exists($cacheKey, self::$reports)) {
            return self::$reports[$cacheKey];
        }

        if (! $site instanceof Site || ! $language instanceof Language) {
            return self::$reports[$cacheKey] = null;
        }

        try {
            return self::$reports[$cacheKey] = BuildPageSeoReportAction::run($record, $site, $language);
        } catch (Throwable) {
            return self::$reports[$cacheKey] = null;
        }
    }

    private static function auditLanguageFor(Page $record): ?Language
    {
        $translation = $record->translations
            ->first(function (Translation $translation): bool {
                $title = $translation->getMeta('title');
                $description = $translation->getMeta('description');

                return $title === null
                    || $title === ''
                    || $description === null
                    || $description === '';
            });

        return $translation?->language;
    }

    private static function schemaStatusFor(Page $record): ?string
    {
        $report = self::reportFor($record);

        if (! $report instanceof PageSeoReportData) {
            return null;
        }

        return $report->schemaReports === []
            ? __('capell-seo-tools::generic.seo_schema_status_missing')
            : __('capell-seo-tools::generic.seo_schema_status_configured');
    }
}
