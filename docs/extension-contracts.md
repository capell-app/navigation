# Worked extension examples

These developer-facing recipes are kept beside the package contract. Replace the example values with the site-specific records and data objects used by the calling workflow.

<!-- example: contract Capell\Navigation\Contracts\NavigationNamesResolver -->

```php
<?php
declare(strict_types=1);
final class ExampleNavigationNamesResolverImplementation implements \Capell\Navigation\Contracts\NavigationNamesResolver
{
    /**
     * Resolve navigation names for the given site and language IDs.
     *
     * @param  array<int, int>  $languageIds
     * @return array<int, string>
     */
    public function resolve(?int $siteId, array $languageIds): array
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\Navigation\Contracts\NavigationNamesResolver::class, ExampleNavigationNamesResolverImplementation::class);
```

<!-- example: contract Capell\Navigation\Contracts\NavigationPageSyncer -->

```php
<?php
declare(strict_types=1);
final class ExampleNavigationPageSyncerImplementation implements \Capell\Navigation\Contracts\NavigationPageSyncer
{
    /**
     * Remove the given page from all navigation items.
     */
    public function removePageFromAllNavigations(\Capell\Core\Contracts\Pageable $page): void
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\Navigation\Contracts\NavigationPageSyncer::class, ExampleNavigationPageSyncerImplementation::class);
```

## Shared cache lifetime

`ResolveNavigationCacheExpiryAction::run($handles, $ttlSeconds = 300)` returns the
earlier of the maximum lifetime and the next navigation/item visibility boundary.
It includes unpublished scheduled candidates, recursively checks both starts and
ends, and ignores malformed dates. Render-model and child-fragment caches use the
same calculation; existing after-commit invalidation and cache stores remain intact.
Applications caching prepared navigation can use this action with their handle
selection and `ScalarizeNavigationDataAction` for enum-free scalar cache payloads.
