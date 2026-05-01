<?php

declare(strict_types=1);

namespace Capell\Events\Livewire\Page;

use Capell\Events\Models\EventOccurrence;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Livewire\Page\AbstractPage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class Events extends AbstractPage
{
    /** @var Collection<string, Collection<int, EventOccurrence>> */
    public Collection $occurrenceGroups;

    protected static string $defaultView = 'capell-events::livewire.page.events';

    protected function setup(): void
    {
        $limit = (int) (Frontend::page()->meta['limit'] ?? config('capell-events.pagination_limit', 12));

        $this->occurrenceGroups = EventOccurrence::query()
            ->with(['event.translation', 'event.pageUrl'])
            ->where('site_id', Frontend::site()->id)
            ->published()
            ->notCancelled()
            ->between(CarbonImmutable::now(), CarbonImmutable::now()->addMonths(12))
            ->orderBy('starts_at')
            ->limit($limit)
            ->get()
            ->groupBy(fn (EventOccurrence $occurrence): string => $occurrence->starts_at->format('Y-m-d'));
    }
}
