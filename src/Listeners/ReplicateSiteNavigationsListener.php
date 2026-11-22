<?php

declare(strict_types=1);

namespace Capell\Navigation\Listeners;

use Capell\Core\Actions\Extensions\RunUnderPackageAdmissionAction;
use Capell\Core\Events\SiteReplicated;
use Capell\Navigation\Actions\ReplicateSiteNavigationsAction;
use Capell\Navigation\Providers\NavigationServiceProvider;

class ReplicateSiteNavigationsListener
{
    public function handle(SiteReplicated $event): void
    {
        RunUnderPackageAdmissionAction::run(NavigationServiceProvider::$packageName, function () use ($event): void {
            if (($event->formData['copy_navigations'] ?? null) !== true) {
                return;
            }

            ReplicateSiteNavigationsAction::run(
                $event->source,
                $event->replica,
                $event->replacementPages,
            );
        });
    }
}
