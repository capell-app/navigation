<?php

declare(strict_types=1);

namespace Capell\Navigation\Support\Loader;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Frontend\Enums\CacheEnum;
use Capell\Frontend\Support\ModelServing\RetrievedModelStore;
use Capell\Navigation\Enums\NavigationHandle;
use Capell\Navigation\Models\Navigation;
use Illuminate\Database\Eloquent\Collection;

class NavigationLoader
{
    public static function getNavigation(NavigationHandle|string $key, Site $site, ?Language $language = null, bool $siteOnlyFallback = true): ?Navigation
    {
        $navigations = $site->navigations;

        $navigation = null;

        if ($language instanceof Language) {
            $navigation = $navigations->where('key', $key)
                ->where('language_id', $language->id)
                ->first();
        }

        if ($siteOnlyFallback && $navigation === null) {
            $navigations = CapellCore::rememberCache(
                CacheEnum::Navigations,
                function (): ?Collection {
                    /** @var class-string<Navigation> $model */
                    $model = Navigation::class;

                    return $model::query()->get();
                },
            );

            $navigation = $navigations->where('key', $key)->first();
        }

        if ($navigation) {
            resolve(RetrievedModelStore::class)->track($navigation);
        }

        return $navigation;
    }

    public static function getNavigationById(int $id): ?Navigation
    {
        $key = CacheEnum::navigationById($id);

        $fromCache = true;

        $navigation = CapellCore::rememberCache($key, function () use ($id, &$fromCache): ?Navigation {
            $fromCache = false;

            /** @var class-string<Navigation> $model */
            $model = Navigation::class;

            return $model::query()->find($id);
        });

        if ($fromCache && $navigation !== null) {
            resolve(RetrievedModelStore::class)->track($navigation);
        }

        return $navigation;
    }
}
