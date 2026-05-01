<?php

declare(strict_types=1);

namespace Capell\Events\Actions;

use Capell\Core\Models\Site;
use Capell\Events\Support\Creator\EventsCreator;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

class InstallPackageAction
{
    use AsFake;
    use AsObject;

    public function handle(): void
    {
        $creator = resolve(EventsCreator::class);

        $creator->createEventPageType();
        $creator->createEventsPageType();
        $creator->createCalendarPageType();
        $creator->createEventLayout();
        $creator->createEventsLayout();
        $creator->createCalendarLayout();

        Site::query()->with('languages')->each(function (Site $site) use ($creator): void {
            $creator->setup($site);
        });
    }
}
