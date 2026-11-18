<?php

declare(strict_types=1);

namespace Capell\Navigation\Adapters;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Support\TableName;
use Capell\Navigation\Actions\RemovePageFromNavigationAction;
use Capell\Navigation\Contracts\NavigationPageSyncer;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Override;

class NavigationPageSyncerAdapter implements NavigationPageSyncer
{
    /**
     * Removes every item that points at the page from live and deleted menus, then drops the page's reference rows,
     * which have no foreign key because a menu item can point at any page model.
     */
    #[Override]
    public function removePageFromAllNavigations(Pageable $page): void
    {
        if (! $page instanceof Model || ! is_numeric($page->getKey())) {
            return;
        }

        $pageableType = $page->getMorphClass();
        $pageableId = (int) $page->getKey();

        Navigation::withTrashed()->chunkById(
            100,
            fn (Collection $navigations) => $navigations->each(
                fn (Navigation $navigation) => RemovePageFromNavigationAction::run($page, $navigation),
            ),
        );

        $references = TableName::resolve('navigation_page_references');

        if (Schema::hasTable($references)) {
            DB::table($references)
                ->where('pageable_type', $pageableType)
                ->where('pageable_id', $pageableId)
                ->delete();
        }
    }
}
