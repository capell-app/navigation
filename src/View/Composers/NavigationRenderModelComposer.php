<?php

declare(strict_types=1);

namespace Capell\Navigation\View\Composers;

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
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

final readonly class NavigationRenderModelComposer
{
    public function compose(View $view): void
    {
        $data = $view->getData();

        if (($data['menu'] ?? null) instanceof NavigationRenderData) {
            return;
        }

        $site = $this->site($data['site'] ?? null);
        $language = $this->language($data['language'] ?? null);
        $page = $this->page($data['page'] ?? null);
        $siteDomain = $this->siteDomain($data['domain'] ?? null, $site, $page);

        $navigation = $this->navigation(
            key: $data['navigationKey'] ?? NavigationHandle::Main,
            site: $site,
            language: $language,
            siteOnlyFallback: ($data['siteOnlyFallback'] ?? true) !== false,
            fallbackWithoutLanguage: ($data['fallbackWithoutLanguage'] ?? false) === true,
        );

        if (! $navigation instanceof Navigation || ! $site instanceof Site || ! $language instanceof Language || ! $page instanceof Model || ! $page instanceof Pageable || ! $siteDomain instanceof SiteDomain) {
            $view->with('menu', null);
            $view->with('navigation', $navigation);

            return;
        }

        $view->with('navigation', $navigation);
        $view->with('menu', BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
            navigation: $navigation,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $siteDomain,
        )));
    }

    private function site(mixed $site): ?Site
    {
        $frontendSite = Frontend::site();

        return $site instanceof Site
            ? $site
            : ($frontendSite instanceof Site ? $frontendSite : null);
    }

    private function language(mixed $language): ?Language
    {
        $frontendLanguage = Frontend::language();

        return $language instanceof Language
            ? $language
            : ($frontendLanguage instanceof Language ? $frontendLanguage : null);
    }

    private function page(mixed $page): ?Pageable
    {
        $frontendPage = Frontend::page();

        return $page instanceof Pageable
            ? $page
            : ($frontendPage instanceof Pageable ? $frontendPage : null);
    }

    private function siteDomain(mixed $domain, ?Site $site, mixed $page): ?SiteDomain
    {
        if ($domain instanceof SiteDomain) {
            return $domain;
        }

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

    private function navigation(mixed $key, ?Site $site, ?Language $language, bool $siteOnlyFallback, bool $fallbackWithoutLanguage): ?Navigation
    {
        if (! $site instanceof Site) {
            return null;
        }

        $navigationKey = $key instanceof NavigationHandle || is_string($key)
            ? $key
            : NavigationHandle::Main;

        $navigation = NavigationLoader::getNavigation($navigationKey, $site, $language, $siteOnlyFallback);

        if ($navigation instanceof Navigation || ! $fallbackWithoutLanguage) {
            return $navigation;
        }

        return NavigationLoader::getNavigation($navigationKey, $site);
    }
}
