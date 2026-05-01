<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Events\Models\Event;
use Capell\Events\Models\EventOccurrence;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsObject;

class SyncEventOccurrencesAction
{
    use AsObject;

    /** @return Collection<int, EventOccurrence> */
    public function handle(Event $event): Collection
    {
        DeleteFutureEventOccurrencesAction::run($event);

        return GenerateEventOccurrencesAction::run($event);
    }
}
