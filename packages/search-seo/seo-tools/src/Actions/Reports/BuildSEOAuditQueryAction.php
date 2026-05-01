<?php

declare(strict_types=1);

namespace Capell\SeoTools\Actions\Reports;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\Actions\Concerns\AsAction;

final class BuildSEOAuditQueryAction
{
    use AsAction;

    public function handle(): Builder
    {
        $query = Page::query()
            ->with(['site', 'translations'])
            ->where(function (Builder $query): void {
                $query->whereDoesntHave('translations')
                    ->orWhereHas('translations', function (Builder $query): void {
                        $query
                            ->whereNull('meta->title')
                            ->orWhere('meta->title', '')
                            ->orWhereNull('meta->description')
                            ->orWhere('meta->description', '');
                    });
            });

        return SiteScope::applyForCurrentActor($query);
    }
}
