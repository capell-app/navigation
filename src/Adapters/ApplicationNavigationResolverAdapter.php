<?php

declare(strict_types=1);

namespace Capell\Navigation\Adapters;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Frontend\Contracts\ApplicationNavigationResolver;
use Capell\Frontend\Data\ApplicationAreaData;
use Capell\Frontend\Data\ApplicationNavigationItemData;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemActiveMode;
use Capell\Navigation\Support\Loader\NavigationItemsLoader;
use Capell\Navigation\Support\Loader\NavigationLoader;
use Capell\Navigation\Support\SafeUrl;
use Illuminate\Http\Request;
use Override;

/**
 * Serves the menu whose handle matches an application area, filtered by the
 * same visitor rules as theme menus and reduced to label, URL and state.
 */
final class ApplicationNavigationResolverAdapter implements ApplicationNavigationResolver
{
    /**
     * @return list<ApplicationNavigationItemData>
     */
    #[Override]
    public function resolve(ApplicationAreaData $area, Site $site, Language $language, Request $request): array
    {
        $navigation = NavigationLoader::getNavigation($area->key, $site, $language);

        if ($navigation === null) {
            return [];
        }

        $siteDomain = SiteDomain::query()
            ->where('site_id', $site->getKey())
            ->where('language_id', $language->getKey())
            ->first() ?? new SiteDomain;

        // Application routes have no CMS page, so an unsaved shell stands in: it
        // matches no saved item and is never offered as a menu destination.
        $loader = new NavigationItemsLoader($navigation, new Page, $site, $language, $siteDomain);

        return $this->items($loader->fetchMenuItems()->all(), $request);
    }

    /**
     * @param  iterable<NavigationItemData>  $items
     * @return list<ApplicationNavigationItemData>
     */
    private function items(iterable $items, Request $request): array
    {
        $result = [];

        foreach ($items as $item) {
            /** @var list<NavigationItemData> $childItems */
            $childItems = $item->children === null ? [] : array_values($item->children->all());
            $children = $this->items($childItems, $request);
            $storedUrl = $item->data['url'] ?? null;
            // The same allowlist theme menus apply: a javascript:, data: or protocol-relative link is never offered.
            $url = is_string($storedUrl) && $storedUrl !== '#' ? SafeUrl::sanitise($storedUrl) : null;
            $label = trim((string) $item->label);

            if ($url === null || $label === '') {
                // A heading with no usable link of its own still leads to the links beneath it.
                array_push($result, ...$children);

                continue;
            }

            $result[] = new ApplicationNavigationItemData(
                label: $label,
                url: $url,
                active: $this->matches($url, $request, $this->activeMode($item))
                    || array_any($children, static fn (ApplicationNavigationItemData $child): bool => $child->active),
                children: $children,
            );
        }

        return $result;
    }

    private function matches(string $url, Request $request, NavigationItemActiveMode $mode): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            // An absolute link only matches when it points at this origin, not another host's same path.
            if (! in_array(strtolower($scheme), ['http', 'https'], true) || ! $this->sameOrigin($url, $request)) {
                return false;
            }
        }

        $path = parse_url($url, PHP_URL_PATH);
        $menuPath = $this->normalisePath(is_string($path) ? $path : '');
        $currentPath = $this->normalisePath($request->getPathInfo());

        if ($menuPath === $currentPath) {
            return true;
        }

        return $mode === NavigationItemActiveMode::StartsWith
            && $menuPath !== ''
            && str_starts_with($currentPath . '/', $menuPath . '/');
    }

    private function sameOrigin(string $url, Request $request): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $port = parse_url($url, PHP_URL_PORT);

        if (! is_string($host) || ! is_string($scheme)) {
            return false;
        }

        $scheme = strtolower($scheme);
        $port = is_int($port) ? $port : ($scheme === 'https' ? 443 : 80);

        return strtolower($host) === strtolower($request->getHost())
            && $scheme === $request->getScheme()
            && $port === (int) $request->getPort();
    }

    private function activeMode(NavigationItemData $item): NavigationItemActiveMode
    {
        $mode = $item->data['active_mode'] ?? null;

        return is_string($mode)
            ? NavigationItemActiveMode::tryFrom($mode) ?? NavigationItemActiveMode::Exact
            : NavigationItemActiveMode::Exact;
    }

    private function normalisePath(string $path): string
    {
        return trim($path, '/');
    }
}
