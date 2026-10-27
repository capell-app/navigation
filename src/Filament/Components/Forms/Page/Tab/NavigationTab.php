<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Components\Forms\Page\Tab;

use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Actions\BuildPageNavigationReferencesAction;
use Capell\Navigation\Filament\Components\Forms\NavigationSelect;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;

class NavigationTab
{
    public static function make(): Tab
    {
        return Tab::make(__('capell-admin::tab.navigation'))
            ->icon('heroicon-o-globe-alt')
            // Replication has form state but no page for references or the page tree.
            ->visible(fn (?Pageable $record): bool => $record instanceof Pageable)
            ->schema([
                Grid::make()
                    ->schema([
                        // Read-only view of the menus that already contain this page. Menu items are
                        // edited in the Navigation editor, so nothing here is persisted.
                        NavigationSelect::make('page_navigations')
                            ->label(__('capell-navigation::generic.page_references'))
                            ->multiple()
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(__('capell-navigation::generic.page_references_empty'))
                            ->afterStateHydrated(function (NavigationSelect $component, ?Pageable $record): void {
                                $component->state(
                                    $record instanceof Pageable
                                        ? BuildPageNavigationReferencesAction::run($record)->modelKeys()
                                        : [],
                                );
                            })
                            ->helperText(__('capell-navigation::generic.page_references_info')),

                        ViewField::make('navigations')
                            ->dehydrated(false)
                            ->visibleOn('edit')
                            ->view('capell-navigation::components.page.navigations'),
                    ]),

                ViewField::make('page_tree')
                    ->dehydrated(false)
                    ->view('capell-admin::components.page.page-tree'),
            ]);
    }
}
