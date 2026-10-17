<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Navigations\Pages;

use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Admin\Filament\Concerns\HasSiteTableFilterTabs;
use Capell\Core\Support\Permissions\SiteAccess;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ListNavigations extends ListRecords
{
    use HasSiteTableFilterTabs;

    protected string $siteRelation = 'navigations';

    /** @return class-string<NavigationResource> */
    #[Override]
    public static function getResource(): string
    {
        return NavigationResource::class;
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.navigation_info');
    }

    /**
     * Resolve badges when rendered, since Filament can build the tab schema
     * before a create or delete action mutates the menus in the same request.
     *
     * @return array<string|int, Tab>
     */
    #[Override]
    public function getCachedTabs(): array
    {
        $tabs = parent::getCachedTabs();

        foreach ($tabs as $key => $tab) {
            if (is_numeric($key)) {
                $tab->badge(static fn (): int => SiteAccess::current()->query(Navigation::class)
                    ->where('site_id', (int) $key)
                    ->count());
            }
        }

        return $tabs;
    }

    #[Override]
    protected function getActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver(),
        ];
    }

    protected function shouldCacheSiteTableFilterTabs(): bool
    {
        return false;
    }

    protected function hasNoSitesFilterTab(): bool
    {
        return false;
    }
}
