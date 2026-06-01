<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Components\Header;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Language;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Facades\Frontend;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Data\NavigationRenderContextData;
use Capell\Navigation\Data\NavigationRenderData;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

final class MainNavigation extends Component
{
    private const string DefaultItemClass = 'nav-item font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:bg-gray-50 lg:!bg-transparent lg:px-4 lg:py-1 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800';

    public string $itemClass;

    public function __construct(?string $itemClass = null)
    {
        $this->itemClass = $itemClass ?? self::DefaultItemClass;
    }

    public function render(): View|string
    {
        $navigation = $this->resolveNavigation();

        if (! $navigation instanceof Navigation) {
            return '';
        }

        $menu = $this->resolveMenu($navigation);

        if (! $menu instanceof NavigationRenderData || $menu->isEmpty()) {
            return '';
        }

        return view('capell-navigation::components.header.navigation', [
            'items' => $menu->items,
            'itemClass' => $this->itemClass,
            'navigation' => $navigation,
        ]);
    }

    private function resolveNavigation(): ?Navigation
    {
        $site = Frontend::site();
        $language = Frontend::language();

        if (! $site instanceof Site || ! $language instanceof Language) {
            return null;
        }

        $navigation = NavigationLoader::getNavigation(NavigationHandle::Main, $site, $language);

        if ($navigation instanceof Navigation) {
            return $navigation;
        }

        return NavigationLoader::getNavigation(NavigationHandle::Main, $site);
    }

    private function resolveMenu(Navigation $navigation): ?NavigationRenderData
    {
        $site = Frontend::site();
        $language = Frontend::language();
        $page = Frontend::page();
        $siteDomain = $this->loadedSiteDomain($site, $page);

        if (! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Pageable || ! $page instanceof Model || ! $siteDomain instanceof SiteDomain) {
            return null;
        }

        return BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $navigation,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $siteDomain,
        ));
    }

    private function loadedSiteDomain(?Site $site, mixed $page): ?SiteDomain
    {
        if ($site instanceof Site && $site->relationLoaded('siteDomain') && $site->siteDomain instanceof SiteDomain) {
            return $site->siteDomain;
        }

        if ($site instanceof Site && $site->relationLoaded('siteDomains')) {
            $siteDomain = $site->siteDomains->first();

            if ($siteDomain instanceof SiteDomain) {
                return $siteDomain;
            }
        }

        if (! $page instanceof Model || ! $page->relationLoaded('pageUrl')) {
            return null;
        }

        $pageUrl = $page->getRelation('pageUrl');

        if (! $pageUrl instanceof PageUrl || ! $pageUrl->relationLoaded('siteDomain')) {
            return null;
        }

        return $pageUrl->siteDomain instanceof SiteDomain ? $pageUrl->siteDomain : null;
    }
}
