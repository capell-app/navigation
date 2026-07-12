<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Loader;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Contracts\RenderedModelTracker;
use Capell\Frontend\Enums\CacheEnum;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NavigationLoader
{
    public static function getNavigation(NavigationHandle|string $key, Site $site, ?Language $language = null, bool $siteOnlyFallback = true): ?Navigation
    {
        $navigationKey = $key instanceof NavigationHandle ? $key->value : $key;
        $navigations = $site->relationLoaded('navigations')
            ? $site->navigations->filter(
                function (Model $navigation): bool {
                    throw_unless($navigation instanceof Navigation);

                    return ! $navigation->isPending() && ! $navigation->isExpired();
                },
            )
            : new Collection;

        $navigation = null;

        if ($language instanceof Language) {
            $navigation = $navigations->where('key', $navigationKey)
                ->where('language_id', $language->id)
                ->first();
        }

        if ($siteOnlyFallback && $navigation === null) {
            if (! $site->relationLoaded('navigations')) {
                $navigations = CapellCore::rememberCache(
                    CacheEnum::siteNavigations((int) $site->getKey()),
                    function () use ($site): Collection {
                        /** @var class-string<Navigation> $model */
                        $model = Navigation::class;

                        return $model::query()
                            ->where(function (Builder $query) use ($site): void {
                                $query->where('site_id', $site->getKey())
                                    ->orWhereNull('site_id');
                            })
                            ->publishedDate()
                            ->get();
                    },
                );
            }

            $navigation = $navigations
                ->where('key', $navigationKey)
                ->filter(
                    fn (Navigation $navigation): bool => $navigation->language_id === null
                        || ($language instanceof Language && $navigation->language_id === $language->getKey()),
                )
                ->sort(
                    fn (Navigation $firstNavigation, Navigation $secondNavigation): int => [
                        $firstNavigation->site_id === $site->getKey() ? 0 : 1,
                        $language instanceof Language && $firstNavigation->language_id === $language->getKey() ? 0 : 1,
                    ] <=> [
                        $secondNavigation->site_id === $site->getKey() ? 0 : 1,
                        $language instanceof Language && $secondNavigation->language_id === $language->getKey() ? 0 : 1,
                    ],
                )
                ->first();
        }

        if ($navigation) {
            resolve(RenderedModelTracker::class)->track($navigation);
        }

        return $navigation;
    }

    public static function getNavigationById(int $id, Site $site, ?Language $language = null): ?Navigation
    {
        $key = sprintf(
            '%s-site-%d-language-%s',
            CacheEnum::navigationById($id),
            $site->getKey(),
            $language instanceof Language ? $language->getKey() : 'any',
        );

        $fromCache = true;

        $navigation = CapellCore::rememberCache($key, function () use ($id, $site, $language, &$fromCache): ?Navigation {
            $fromCache = false;

            /** @var class-string<Navigation> $model */
            $model = Navigation::class;

            return $model::query()
                ->where(function (Builder $query) use ($site): void {
                    $query->where('site_id', $site->getKey())
                        ->orWhereNull('site_id');
                })
                ->where(function (Builder $query) use ($language): void {
                    $query->whereNull('language_id');

                    if ($language instanceof Language) {
                        $query->orWhere('language_id', $language->getKey());
                    }
                })
                ->publishedDate()
                ->find($id);
        });

        if ($fromCache && $navigation !== null) {
            resolve(RenderedModelTracker::class)->track($navigation);
        }

        return $navigation;
    }
}
