<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Actions\ResolvePublicPageableMorphTypesAction;
use Capell\Core\Contracts\Pageable;
use Capell\Navigation\Data\NavigationItemAvailabilityData;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Enums\NavigationItemVisibility;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Support\SafeUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Spatie\LaravelData\DataCollection;

/** @method static list<NavigationItemAvailabilityData> run(Navigation $navigation) */
final class CheckSavedNavigationItemsAction
{
    use AsFake;
    use AsObject;

    /** @return list<NavigationItemAvailabilityData> */
    public function handle(Navigation $navigation): array
    {
        Gate::authorize('update', $navigation);
        $saved = $navigation->fresh();

        if ($saved === null) {
            return [];
        }

        Gate::authorize('update', $saved);
        $items = $saved->items;

        if (! $items instanceof DataCollection) {
            $items = NavigationItemData::collect($items ?? [], DataCollection::class);
        }

        /** @var list<NavigationItemData> $itemList */
        $itemList = $items->all();

        return $this->checkItems($saved, $itemList);
    }

    /**
     * @param  iterable<NavigationItemData>  $items
     * @return list<NavigationItemAvailabilityData>
     */
    private function checkItems(Navigation $navigation, iterable $items, string $parent = '', bool $parentOmitted = false): array
    {
        $rows = [];
        $index = 0;

        foreach ($items as $item) {
            $index++;
            $position = $parent === '' ? (string) $index : $parent . '.' . $index;
            $status = $this->status($navigation, $item, $parentOmitted);
            $rows[] = new NavigationItemAvailabilityData($position, $status);
            /** @var list<NavigationItemData> $childItems */
            $childItems = $item->children?->all() ?? [];
            $rows = [...$rows, ...$this->checkItems(
                $navigation,
                $childItems,
                $position,
                in_array($status, ['hidden', 'audience', 'parent_omitted', 'not_published', 'target_disabled', 'unavailable'], true),
            )];
        }

        return $rows;
    }

    private function status(Navigation $navigation, NavigationItemData $item, bool $parentOmitted): string
    {
        if ($parentOmitted) {
            return 'parent_omitted';
        }

        if (! $item->is_visible) {
            return 'hidden';
        }

        $rawVisibility = $item->data['visibility'] ?? null;
        $visibility = is_string($rawVisibility)
            ? NavigationItemVisibility::tryFrom($rawVisibility) ?? NavigationItemVisibility::Everyone
            : NavigationItemVisibility::Everyone;
        $ability = $item->data['ability'] ?? null;
        $anonymousCanSee = match ($visibility) {
            NavigationItemVisibility::Everyone, NavigationItemVisibility::Guests => true,
            NavigationItemVisibility::Ability => is_string($ability) && $ability !== '' && Gate::forUser(null)->allows($ability),
            NavigationItemVisibility::Authenticated, NavigationItemVisibility::Role => false,
        };

        if (! $anonymousCanSee) {
            return 'audience';
        }

        if ($item->type !== NavigationItemType::Page) {
            if ($item->type === NavigationItemType::Heading) {
                return 'ready';
            }

            $url = $item->data['url'] ?? null;

            return is_string($url) && SafeUrl::isSafe($url) ? 'ready' : 'invalid_url';
        }

        $type = $item->data['pageable_type'] ?? null;
        $id = $item->data['pageable_id'] ?? null;
        $modelClass = is_string($type) ? Relation::getMorphedModel($type) ?? $type : null;

        if (! is_string($modelClass) || ! in_array($modelClass, ResolvePublicPageableMorphTypesAction::run(), true)
            || ! is_subclass_of($modelClass, Model::class) || ! is_subclass_of($modelClass, Pageable::class)
            || ! is_numeric($id)) {
            return 'unavailable';
        }

        /** @var class-string<Model&Pageable<Model>> $modelClass */
        $page = SiteScope::applyForCurrentActor($modelClass::query(), denyWhenMissingActor: true)
            ->when($navigation->site_id !== null, fn (Builder $query): Builder => $query->where('site_id', $navigation->site_id))
            ->find((int) $id);

        if ($page === null || Gate::denies('view', $page)) {
            return 'unavailable';
        }

        if (! $modelClass::query()->whereKey($page->getKey())->publishedDate()->exists()) {
            return 'not_published';
        }

        $siteId = $page->getAttribute('site_id');

        if (! is_int($siteId)) {
            return 'unavailable';
        }

        return ResolvePublicNavigationPagesAction::run($modelClass, [(int) $id], $siteId)->isNotEmpty()
            ? 'ready'
            : 'target_disabled';
    }
}
