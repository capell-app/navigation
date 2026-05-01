<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Core\Models\Layout;
use Capell\Events\Support\Creator\EventsCreator;
use Lorisleiva\Actions\Concerns\AsObject;

class GetEventLayoutAction
{
    use AsObject;

    public function handle(): ?Layout
    {
        return resolve(EventsCreator::class)->createEventLayout();
    }
}
