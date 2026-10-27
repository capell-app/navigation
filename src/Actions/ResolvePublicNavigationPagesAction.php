<?php

declare(strict_types=1);

namespace Capell\Navigation\Actions;

use Capell\Core\Contracts\Pageable;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static Collection<int, Model&Pageable<Model>> run(class-string<Model&Pageable<Model>> $modelClass, list<int> $ids, int $siteId) */
final class ResolvePublicNavigationPagesAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  class-string<Model&Pageable<Model>>  $modelClass
     * @param  list<int>  $ids
     * @return Collection<int, Model&Pageable<Model>>
     */
    public function handle(string $modelClass, array $ids, int $siteId): Collection
    {
        $model = new $modelClass;

        return $modelClass::query()
            ->with(['translation', 'pageUrl'])
            ->whereIn($model->getKeyName(), $ids)
            ->where('site_id', $siteId)
            ->publishedDate()
            ->whereHas('blueprint', fn (BuilderContract $query): BuilderContract => $query->enabled()->accessible())
            ->whereHas('pageUrl', fn (BuilderContract $query): BuilderContract => $query->enabled())
            ->orderBy($model->getKeyName())
            ->get();
    }
}
