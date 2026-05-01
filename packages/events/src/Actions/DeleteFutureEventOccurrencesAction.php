<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Events\Models\Event;
use Carbon\CarbonImmutable;
use Lorisleiva\Actions\Concerns\AsObject;

class DeleteFutureEventOccurrencesAction
{
    use AsObject;

    public function handle(Event $event, ?CarbonImmutable $from = null): int
    {
        $from ??= CarbonImmutable::now();

        return $event->occurrences()
            ->where('starts_at', '>=', $from)
            ->delete();
    }
}
