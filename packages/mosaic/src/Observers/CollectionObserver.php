<?php

declare(strict_types=1);

namespace Capell\Mosaic\Observers;

use Capell\Core\Enums\CacheEnum;
use Capell\Core\Models\Type;
use Capell\Core\Support\CapellCoreHelper;
use Capell\Mosaic\Enums\LayoutTypeEnum;
use Capell\Mosaic\Models\Collection;
use InvalidArgumentException;

class CollectionObserver
{
    public function creating(Collection $collection): void
    {
        if (! $collection->type_id) {
            $collection->type_id = Type::query()->where('type', LayoutTypeEnum::Content)->default()->value('id');
            throw_unless($collection->type_id, InvalidArgumentException::class, 'Unable to create collection without a type.');
        }

        // Normalize parent_id from loaded relation if needed (nested set).
        if ($collection->parent_id !== null) {
            $parent = $collection->getRelationValue('parent');
            if ($parent !== null && $collection->parent_id !== $parent->id) {
                $collection->parent_id = $parent->id;
            }
        }
    }

    public function saving(Collection $collection): void
    {
        if (method_exists($collection, 'nodeCallPendingAction')) {
            $collection->nodeCallPendingAction();
        }
    }

    public function deleting(Collection $collection): void
    {
        if (method_exists($collection, 'nodeRefreshNode')) {
            $collection->nodeRefreshNode();
        }
    }

    public function deleted(Collection $collection): void
    {
        if (method_exists($collection, 'nodeDeleteDescendants')) {
            $collection->nodeDeleteDescendants();
        }

        // Shadow-column maintenance runs in the BelongsToWorkspace trait's
        // `deleting` hook, before this observer fires.

        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }

    public function restoring(Collection $collection): void {}

    public function restored(Collection $collection): void
    {
        CapellCoreHelper::flushCache([
            CacheEnum::RelationExists,
        ]);
    }
}
