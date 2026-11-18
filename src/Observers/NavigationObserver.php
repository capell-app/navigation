<?php

declare(strict_types=1);

namespace Capell\Navigation\Observers;

use BackedEnum;
use Capell\Core\Actions\GenerateUniqueKeyAction;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Site;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Frontend\Enums\CacheEnum as FrontendCacheEnum;
use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
use Capell\Navigation\Actions\SyncNavigationPageReferencesAction;
use Capell\Navigation\Enums\NavigationCacheEnum;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\NavigationKeyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnexpectedValueException;

class NavigationObserver
{
    public function saving(Navigation $navigation): void
    {
        if ($navigation->key === null || $navigation->key === '') {
            $navigation->key = GenerateUniqueKeyAction::run($navigation);
        }

        $this->claimKeyScope($navigation);
    }

    public function saved(Navigation $navigation): void
    {
        SyncNavigationPageReferencesAction::run($navigation);

        $this->clearCache($navigation);
    }

    public function deleted(Navigation $navigation): void
    {
        if (! $navigation->isForceDeleting()) {
            // A deleted menu gives its key back, so a replacement can be created while it waits in the bin.
            $navigation->newQueryWithoutScopes()->whereKey($navigation->getKey())->update(['active_key_scope' => null]);
            $navigation->setAttribute('active_key_scope', null);
            $navigation->syncOriginalAttribute('active_key_scope');
        }

        $this->clearCache($navigation);
    }

    public function restored(Navigation $navigation): void
    {
        $this->clearCache($navigation);
    }

    /**
     * A live menu claims its key within its site and language, where "all sites" and "all languages" count as one
     * scope each. Restoring a deleted menu whose key has since been reused is refused with a message instead of a
     * database error.
     */
    private function claimKeyScope(Navigation $navigation): void
    {
        if ($navigation->trashed()) {
            $navigation->setAttribute('active_key_scope', null);

            return;
        }

        // Saves that leave the key, site, language and bin state alone keep the claim they already hold, so content
        // edits never fail on a duplicate that predates the scope column.
        if ($navigation->exists && ! $navigation->isDirty(['key', 'site_id', 'language_id', 'deleted_at'])) {
            return;
        }

        $key = $navigation->key;
        $keyValue = $key instanceof BackedEnum ? (string) $key->value : (string) $key;

        $scope = NavigationKeyScope::for(
            $keyValue,
            is_numeric($navigation->site_id) ? (int) $navigation->site_id : null,
            is_numeric($navigation->language_id) ? (int) $navigation->language_id : null,
        );

        $taken = Navigation::query()
            ->where('active_key_scope', $scope)
            ->when($navigation->exists, fn (Builder $query): Builder => $query->whereKeyNot($navigation->getKey()))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'key' => __('capell-navigation::navigation.key_in_use', ['key' => $keyValue]),
            ]);
        }

        $navigation->setAttribute('active_key_scope', $scope);
    }

    private function clearCache(Navigation $navigation): void
    {
        CapellCoreHelper::flushCache([NavigationCacheEnum::NavigationNames->value]);
        BuildNavigationRenderModelAction::flushSharedRenderModelCache();

        CapellCore::removeCacheKey(FrontendCacheEnum::Navigations->value);
        CapellCore::removeCacheKey(FrontendCacheEnum::navigationById((int) $navigation->getKey()));

        $siteIds = collect([$navigation->site_id, $navigation->getOriginal('site_id')])
            ->filter(fn (mixed $siteId): bool => is_numeric($siteId))
            ->map(fn (mixed $siteId): int => (int) $siteId)
            ->unique();

        if ($navigation->site_id === null || $navigation->getOriginal('site_id') === null) {
            $siteIds = $siteIds->merge(Site::query()->pluck('id')->map(static function (mixed $siteId): int {
                if (! is_int($siteId)) {
                    throw new UnexpectedValueException('Site identifiers must be integers.');
                }

                return $siteId;
            }));
        }

        foreach ($siteIds->unique() as $siteId) {
            CapellCore::removeCacheKey(FrontendCacheEnum::siteNavigations($siteId));
        }

        $cacheKeys = collect([
            [
                'key' => $navigation->key,
                'site_id' => $navigation->site_id,
                'language_id' => $navigation->language_id,
            ],
            [
                'key' => $navigation->getOriginal('key'),
                'site_id' => $navigation->getOriginal('site_id'),
                'language_id' => $navigation->getOriginal('language_id'),
            ],
        ]);

        foreach ($cacheKeys as $cacheKey) {
            if (! is_string($cacheKey['key'])) {
                continue;
            }

            if (! is_numeric($cacheKey['site_id'])) {
                continue;
            }

            CapellCore::removeCacheKey(FrontendCacheEnum::navigation(
                $cacheKey['key'],
                (int) $cacheKey['site_id'],
                is_numeric($cacheKey['language_id']) ? (int) $cacheKey['language_id'] : null,
            ));
        }
    }
}
