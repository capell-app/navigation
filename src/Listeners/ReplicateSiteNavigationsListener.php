<?php

declare(strict_types=1);

namespace Capell\Navigation\Listeners;

use Capell\Core\Events\SiteReplicated;
use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Actions\ReplicateSiteNavigationsAction;
use Capell\Navigation\Providers\NavigationServiceProvider;

class ReplicateSiteNavigationsListener
{
    public function handle(SiteReplicated $event): void
    {
        if (! CapellCore::isPackageEnabled(NavigationServiceProvider::$packageName)) {
            return;
        }

        if (($event->formData['copy_navigations'] ?? null) !== true) {
            return;
        }

        ReplicateSiteNavigationsAction::run(
            $event->source,
            $event->replica,
            $event->replacementPages,
        );
    }
}
